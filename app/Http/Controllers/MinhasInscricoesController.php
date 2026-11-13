<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use App\Services\CobrancaService;
use App\Services\FrequenciaService;
use Illuminate\Support\Facades\Gate;

class MinhasInscricoesController extends Controller
{
    public function index(FrequenciaService $frequencia)
    {
        $matriculas = auth()->user()->matriculas()
            ->with(['turma.oferecimento.atividade', 'turma.professor', 'metadados', 'presencas', 'cobrancas'])
            ->latest()
            ->get();

        return view('minhas-inscricoes.index', [
            'matriculas' => $matriculas,
            'frequencia' => $frequencia,
        ]);
    }

    public function cancelar(Matricula $matricula, CobrancaService $cobrancas)
    {
        Gate::authorize('cancel', $matricula);
        abort_if($matricula->status === Matricula::CANCELADA, 422);

        $cobrancas->cancelarMatricula($matricula);
        $matricula->refresh();

        return redirect('/minhas-inscricoes')
            ->with(
                $matricula->revisao_credito_sru ? 'alert-warning' : 'alert-success',
                $matricula->revisao_credito_sru
                    ? 'Inscrição cancelada. O SRU analisará eventual crédito; pagamentos já realizados não são reembolsados automaticamente.'
                    : 'Inscrição cancelada.',
            );
    }
}
