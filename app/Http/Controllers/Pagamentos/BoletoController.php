<?php

namespace App\Http\Controllers\Pagamentos;

use App\Exceptions\BoletoIntegracaoException;
use App\Http\Controllers\Controller;
use App\Models\Cobranca;
use App\Models\Matricula;
use App\Services\CobrancaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BoletoController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Cobranca::class);

        $cobrancas = Cobranca::with(['matricula.user', 'matricula.turma.oferecimento.atividade'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->boolean('revisao_credito_sru'), fn ($q) => $q->whereHas('matricula', fn ($q) => $q->where('revisao_credito_sru', true)))
            ->when($request->filled('search'), function ($q) use ($request) {
                $busca = '%'.strtr($request->query('search'), ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
                $q->whereHas('matricula.user', fn ($q) => $q->whereRaw("name like ? escape '!'", [$busca])->orWhereRaw("email like ? escape '!'", [$busca]));
            })
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('boletos.index', ['cobrancas' => $cobrancas]);
    }

    public function pdf(Matricula $matricula, Cobranca $cobranca, CobrancaService $service)
    {
        abort_unless($cobranca->matricula_id === $matricula->id, 404);
        Gate::authorize('view', $cobranca);

        try {
            $pdf = $service->obterPdf($cobranca);
        } catch (BoletoIntegracaoException) {
            abort(503, 'O boleto está temporariamente indisponível. Tente novamente mais tarde.');
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="boleto-'.$cobranca->parcela.'.pdf"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
