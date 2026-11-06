<?php

namespace App\Http\Controllers\Carteiras;

use App\Http\Controllers\Controller;
use App\Http\Requests\CarteiraRequest;
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
        return view('minhas-carteiras.index', [
            'carteiras' => $request->user()->carteirasSolicitadas()->latest()->get(),
            'categorias' => $this->carteiras->categoriasPermitidas($request->user()),
        ]);
    }

    public function create(Request $request)
    {
        $categorias = $this->carteiras->categoriasPermitidas($request->user());
        $categoria = $request->query('categoria', array_key_first($categorias));
        abort_unless(isset($categorias[$categoria]), 404);

        $config = config("cepe.carteiras.categorias.{$categoria}");

        // documentos comuns à categoria e, por parentesco, os do beneficiário (o JS mostra só os do parentesco escolhido)
        $porParentesco = collect($config['parentescos'] ?? [])
            ->map(fn ($docs, $parentesco) => array_values(array_diff($this->carteiras->documentosExigidos($categoria, $parentesco), $config['documentos'])))
            ->all();

        return view('minhas-carteiras.create', [
            'categorias' => $categorias,
            'categoria' => $categoria,
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
        $carteira = $this->carteiras->solicitar($request->user(), $request->categoria(), $beneficiario, $arquivos);

        return redirect("/minhas-carteiras/{$carteira->id}")->with('alert-success', 'Solicitação enviada e aguardando análise da SRU.');
    }

    public function show(Carteira $carteira)
    {
        Gate::authorize('view', $carteira);
        $carteira->load('documentos', 'analisador');

        return view('minhas-carteiras.show', [
            'carteira' => $carteira,
            'tipos' => [
                ...$this->carteiras->documentosExigidos($carteira->categoria, $carteira->parentesco),
                ...$this->carteiras->documentosOpcionais($carteira->categoria),
            ],
        ]);
    }

    /** Reenvio de documentos quando a SRU marca a documentação como pendente. */
    public function reenviar(Request $request, Carteira $carteira)
    {
        Gate::authorize('reenviar', $carteira);

        $tipos = [
            ...$this->carteiras->documentosExigidos($carteira->categoria, $carteira->parentesco),
            ...$this->carteiras->documentosOpcionais($carteira->categoria),
        ];
        $rules = ['documentos' => 'required|array'];
        foreach ($tipos as $tipo) {
            $rules["documentos.{$tipo}"] = 'nullable|'.CarteiraRequest::regraArquivo($tipo);
        }
        $request->validate($rules, ['documentos.required' => 'Envie ao menos um documento.']);

        $this->carteiras->guardarDocumentos($carteira, array_intersect_key($request->file('documentos', []), array_flip($tipos)));
        $carteira->update(['status' => 'em_analise']);

        return redirect("/minhas-carteiras/{$carteira->id}")->with('alert-success', 'Documentos reenviados para análise.');
    }

    /** Dependente com conta própria vincula a carteira ao seu perfil informando o número. */
    public function vincular(Request $request)
    {
        $dados = $request->validate(['numero' => 'required|digits_between:1,20']);
        $this->carteiras->vincular($request->user(), $dados['numero']);

        return redirect('/perfil')->with('alert-success', 'Carteira CEPEUSP vinculada ao seu perfil.');
    }
}
