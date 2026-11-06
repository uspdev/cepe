<?php

namespace App\Http\Controllers\Carteiras;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnaliseCarteiraRequest;
use App\Models\Carteira;
use App\Models\CarteiraDocumento;
use App\Services\CarteiraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class CarteiraController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Carteira::class);
        $status = $request->has('status') ? $request->query('status') : 'em_analise';

        $carteiras = Carteira::with('solicitante')->latest()
            ->when($status === 'vencida', fn ($q) => $q->whereNotIn('status', ['em_analise', 'pendente', 'reprovada', 'inativa'])->whereDate('validade_fim', '<', now()->toDateString()))
            ->when(! in_array($status, ['vencida', 'todos', null, ''], true), fn ($q) => $q->where('status', $status))
            ->when($request->filled('categoria'), fn ($q) => $q->where('categoria', $request->query('categoria')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $busca = '%'.strtr($request->query('search'), ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
                $q->where(fn ($q) => $q->whereRaw("nome like ? escape '!'", [$busca])
                    ->orWhereRaw("numero like ? escape '!'", [$busca])
                    ->orWhereHas('solicitante', fn ($q) => $q->whereRaw("name like ? escape '!'", [$busca])->orWhereRaw("email like ? escape '!'", [$busca])));
            })
            ->paginate(50)
            ->withQueryString();

        return view('carteiras.index', ['carteiras' => $carteiras]);
    }

    public function show(Carteira $carteira)
    {
        Gate::authorize('analisar', $carteira);
        $carteira->load('solicitante.perfil', 'user', 'documentos', 'analisador');

        return view('carteiras.show', ['carteira' => $carteira]);
    }

    public function analise(AnaliseCarteiraRequest $request, Carteira $carteira, CarteiraService $carteiras)
    {
        $dados = $request->validated();

        if ($dados['status'] === 'aprovada' && $carteira->status !== 'aprovada') {
            $carteiras->aprovar($carteira, $dados['numero'], $request->user(), $dados['via'] ?? null, $dados['observacao'] ?? null);
        } elseif ($dados['status'] !== $carteira->status) {
            $carteiras->transitar($carteira, $dados['status'], $request->user(), $dados['observacao'] ?? null);
        }

        // número e via seguem editáveis após a aprovação (correção ou nova via manual)
        if ($carteira->fresh()->numero !== null) {
            $numero = (string) ($dados['numero'] ?? $carteira->numero);
            $via = isset($dados['via']) ? (int) $dados['via'] : $carteira->via;
            if ($numero !== $carteira->numero || $via !== $carteira->via) {
                $carteiras->atualizarEmissao($carteira, $numero, $via, $request->user());
            }
        }

        return redirect("/carteiras/{$carteira->id}")->with('alert-success', 'Análise registrada.');
    }

    public function documento(Carteira $carteira, CarteiraDocumento $documento)
    {
        Gate::authorize('view', $carteira);
        abort_unless($documento->carteira_id === $carteira->id && Storage::disk('local')->exists($documento->arquivo), 404);

        return Storage::disk('local')->download($documento->arquivo);
    }
}
