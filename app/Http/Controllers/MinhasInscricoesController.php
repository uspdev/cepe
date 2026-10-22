<?php

namespace App\Http\Controllers;

use App\Models\Matricula;

class MinhasInscricoesController extends Controller
{
    public function index()
    {
        $matriculas = auth()->user()->matriculas()
            ->with(['turma.oferecimento.atividade', 'metadados'])
            ->latest()
            ->get();

        return view('minhas-inscricoes.index', ['matriculas' => $matriculas]);
    }

    public function cancelar(Matricula $matricula)
    {
        abort_if($matricula->user_id !== auth()->id(), 403);
        abort_if($matricula->status !== Matricula::MATRICULADA, 422);

        $matricula->update(['status' => Matricula::CANCELADA]);

        return redirect('/minhas-inscricoes')
            ->with('alert-success', 'Inscrição cancelada.');
    }
}
