<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use App\Models\Perfil;
use App\Services\FrequenciaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MinhasInscricoesController extends Controller
{
    public function index(FrequenciaService $frequencia)
    {
        $matriculas = auth()->user()->matriculas()
            ->with(['turma.oferecimento.atividade', 'turma.professor', 'metadados', 'presencas'])
            ->latest()
            ->get();

        return view('minhas-inscricoes.index', [
            'matriculas' => $matriculas,
            'frequencia' => $frequencia,
        ]);
    }

    public function cancelar(Matricula $matricula)
    {
        Gate::authorize('cancel', $matricula);
        abort_if($matricula->status !== Matricula::MATRICULADA, 422);

        DB::transaction(function () use ($matricula) {
            // transição condicional: cancelamentos simultâneos não estornam o crédito duas vezes
            $cancelada = Matricula::whereKey($matricula->id)
                ->where('status', Matricula::MATRICULADA)
                ->update(['status' => Matricula::CANCELADA]);
            abort_if($cancelada === 0, 422);

            if ($matricula->credito_usado != 0) {
                $perfil = Perfil::where('user_id', $matricula->user_id)->lockForUpdate()->first();
                $perfil?->update(['credito' => max(0, $perfil->credito + $matricula->credito_usado)]);
            }
        });

        return redirect('/minhas-inscricoes')
            ->with('alert-success', 'Inscrição cancelada.');
    }
}
