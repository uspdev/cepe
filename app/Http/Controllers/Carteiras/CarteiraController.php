<?php

namespace App\Http\Controllers\Carteiras;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnaliseCarteiraRequest;
use App\Models\Carteira;
use App\Models\CarteiraDocumento;
use App\Models\SolicitacaoCarteira;
use App\Models\User;
use App\Services\CarteiraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CarteiraController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Carteira::class);
        $status = $request->query('status', 'em_analise');
        $statuses = array_keys(SolicitacaoCarteira::TRANSICOES);

        $solicitacoes = SolicitacaoCarteira::with('solicitante', 'carteira')->latest()
            ->when($status !== 'todos' && in_array($status, $statuses, true), fn ($q) => $q->where('status', $status))
            ->when($request->filled('categoria'), fn ($q) => $q->where('categoria', $request->query('categoria')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $busca = '%'.strtr($request->query('search'), ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
                $q->where(fn ($q) => $q->whereRaw("nome like ? escape '!'", [$busca])
                    ->orWhereRaw("cpf like ? escape '!'", [$busca])
                    ->orWhereHas('carteira', fn ($q) => $q->whereRaw("numero like ? escape '!'", [$busca]))
                    ->orWhereHas('solicitante', fn ($q) => $q->whereRaw("name like ? escape '!'", [$busca])->orWhereRaw("email like ? escape '!'", [$busca])));
            })
            ->paginate(50)
            ->withQueryString();

        return view('carteiras.index', compact('solicitacoes'));
    }

    public function show(SolicitacaoCarteira $solicitacao)
    {
        Gate::authorize('analisar', $solicitacao);
        $solicitacao->load('solicitante.perfil', 'carteira.user', 'documentos', 'analisador');

        return view('carteiras.show', compact('solicitacao'));
    }

    public function analise(AnaliseCarteiraRequest $request, SolicitacaoCarteira $solicitacao, CarteiraService $carteiras)
    {
        $dados = $request->validated();

        if ($dados['status'] === 'aprovada') {
            $carteiras->aprovar($solicitacao, $dados['numero'], $request->user(), $dados['via'] ?? null, $dados['observacao'] ?? null);
        } else {
            $carteiras->registrarAnalise($solicitacao, $dados['status'], $dados['observacao'] ?? '', $request->user());
        }

        return redirect("/carteiras/{$solicitacao->id}")->with('alert-success', 'Análise registrada.');
    }

    public function transitar(Request $request, Carteira $carteira, CarteiraService $carteiras)
    {
        Gate::authorize('analisar', $carteira);
        $dados = $request->validate([
            'status' => ['required', Rule::in($carteira->proximosStatus())],
            'observacao' => 'nullable|string',
        ]);

        $carteiras->transitar($carteira, $dados['status'], $request->user(), $dados['observacao'] ?? null);

        return back()->with('alert-success', 'Status da carteira atualizado.');
    }

    public function documento(SolicitacaoCarteira $solicitacao, CarteiraDocumento $documento)
    {
        Gate::authorize('view', $solicitacao);
        abort_unless($documento->solicitacao_id === $solicitacao->id && Storage::disk('local')->exists($documento->arquivo), 404);

        return Storage::disk('local')->download($documento->arquivo);
    }

    public function vincular(Request $request, User $user, CarteiraService $carteiras)
    {
        Gate::authorize('carteiras.vincular');
        $dados = $request->validate([
            'carteira_id' => ['required', 'integer', Rule::exists('carteiras', 'id')],
        ]);
        $carteiras->vincular($user, Carteira::findOrFail($dados['carteira_id']));

        return redirect("/usuarios/{$user->id}/perfil")->with('success', 'Carteira vinculada ao usuário.');
    }

    public function criarParaUsuario(Request $request, User $user, CarteiraService $carteiras)
    {
        Gate::authorize('carteiras.vincular');
        $categoria = $request->input('categoria');
        $config = is_string($categoria) ? config("cepe.carteiras.categorias.{$categoria}") : null;
        $dados = $request->validate([
            'numero' => 'required|digits_between:1,20|unique:carteiras,numero',
            'categoria' => ['required', Rule::in(array_keys(config('cepe.carteiras.categorias')))],
            'status' => ['required', Rule::in(Carteira::STATUS_GERENCIAVEIS)],
            'parentesco' => [
                Rule::requiredIf(($config['beneficiario'] ?? null) === 'dependente'),
                'nullable',
                Rule::in(array_keys($config['parentescos'] ?? [])),
            ],
            'via' => 'nullable|integer|min:1',
            'validade_inicio' => 'nullable|date',
            'validade_fim' => 'required|date|after_or_equal:validade_inicio',
        ]);

        $carteiras->criarParaUsuario($user, $dados);

        return redirect("/usuarios/{$user->id}/perfil")->with('success', 'Carteira criada e vinculada ao usuário.');
    }

    public function desvincular(User $user, Carteira $carteira, CarteiraService $carteiras)
    {
        Gate::authorize('carteiras.vincular');
        $carteiras->desvincular($user, $carteira);

        return redirect("/usuarios/{$user->id}/perfil")->with('success', 'Carteira desvinculada do usuário.');
    }
}
