<?php

namespace App\Http\Controllers\Carteiras;

use App\Http\Controllers\Controller;
use App\Http\Requests\CarteiraRequest;
use App\Models\SolicitacaoCarteira;
use App\Models\Carteira;
use App\Services\CarteiraService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MinhasCarteirasController extends Controller
{
    public function __construct(private CarteiraService $carteiras)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $carteirasRenovaveis = Carteira::where('user_id', $user->id)
            ->orWhereHas('solicitacoes', fn ($q) => $q->where('solicitante_id', $user->id))
            ->get()
            ->filter(fn (Carteira $carteira) => $this->carteiras->podeRenovar($user, $carteira))
            ->values();

        return view('minhas-carteiras.index', [
            'solicitacoes' => $user->carteirasSolicitadas()->with('carteira')->latest()->get(),
            'categorias' => $this->carteiras->categoriasPermitidas($user),
            'carteirasRenovaveis' => $carteirasRenovaveis,
        ]);
    }

    public function create(Request $request)
    {
        $categorias = $this->carteiras->categoriasPermitidas($request->user());
        $renovar = null;
        if ($request->filled('renovar')) {
            $renovar = Carteira::findOrFail($request->query('renovar'));
            abort_unless($this->carteiras->podeRenovar($request->user(), $renovar), 404);
            $categorias[$renovar->categoria] = config("cepe.carteiras.categorias.{$renovar->categoria}");
        }
        $categoria = $renovar?->categoria ?? $request->query('categoria', array_key_first($categorias));
        abort_unless(isset($categorias[$categoria]), 404);

        $config = config("cepe.carteiras.categorias.{$categoria}");

        // documentos comuns à categoria e, por parentesco, os do beneficiário (o JS mostra só os do parentesco escolhido)
        $porParentesco = collect($config['parentescos'] ?? [])
            ->map(fn ($docs, $parentesco) => array_values(array_diff($this->carteiras->documentosExigidos($categoria, $parentesco), $config['documentos'])))
            ->all();

        return view('minhas-carteiras.create', [
            'categorias' => $categorias,
            'categoria' => $categoria,
            'renovar' => $renovar,
            'config' => $config,
            'comuns' => $this->carteiras->documentosExigidos($categoria, null),
            'porParentesco' => $porParentesco,
            'opcionais' => $this->carteiras->documentosOpcionais($categoria),
        ]);
    }

    public function store(CarteiraRequest $request)
    {
        $beneficiario = $request->informaBeneficiario() ? [
            'nome' => $request->validated('nome'),
            'cpf' => $request->validated('cpf') ?: null,
            'data_nascimento' => Carbon::createFromFormat('d/m/Y', $request->validated('data_nascimento'))->toDateString(),
            'parentesco' => $request->validated('parentesco'),
        ] : [];

        $arquivos = array_intersect_key($request->file('documentos', []), array_flip($request->tiposDocumento()));
        $renovar = $request->validated('carteira_id')
            ? Carteira::findOrFail($request->validated('carteira_id'))
            : null;
        $solicitacao = $this->carteiras->solicitar($request->user(), $request->categoria(), $beneficiario, $arquivos, $renovar);

        return redirect("/minhas-carteiras/{$solicitacao->id}")->with('alert-success', 'Solicitação enviada e aguardando análise da SRU.');
    }

    public function show(SolicitacaoCarteira $solicitacao)
    {
        Gate::authorize('view', $solicitacao);
        $solicitacao->load('documentos', 'analisador', 'carteira');

        return view('minhas-carteiras.show', [
            'solicitacao' => $solicitacao,
            'tipos' => [
                ...$this->carteiras->documentosExigidos($solicitacao->categoria, $solicitacao->parentesco),
                ...$this->carteiras->documentosOpcionais($solicitacao->categoria),
            ],
        ]);
    }

    /** Reenvio de documentos quando a SRU marca a documentação como pendente. */
    public function reenviar(Request $request, SolicitacaoCarteira $solicitacao)
    {
        Gate::authorize('reenviar', $solicitacao);

        $tipos = [
            ...$this->carteiras->documentosExigidos($solicitacao->categoria, $solicitacao->parentesco),
            ...$this->carteiras->documentosOpcionais($solicitacao->categoria),
        ];
        $rules = ['documentos' => 'required|array'];
        foreach ($tipos as $tipo) {
            $rules["documentos.{$tipo}"] = 'nullable|'.CarteiraRequest::regraArquivo($tipo);
        }
        $request->validate($rules, ['documentos.required' => 'Envie ao menos um documento.']);

        $this->carteiras->guardarDocumentos($solicitacao, array_intersect_key($request->file('documentos', []), array_flip($tipos)));
        $solicitacao->update(['status' => 'em_analise']);

        return redirect("/minhas-carteiras/{$solicitacao->id}")->with('alert-success', 'Documentos reenviados para análise.');
    }
}
