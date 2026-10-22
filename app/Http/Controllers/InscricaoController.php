<?php

namespace App\Http\Controllers;

use App\Http\Requests\InscricaoRequest;
use App\Models\Turma;
use App\Services\InscricaoService;

class InscricaoController extends Controller
{
    public function __construct(private InscricaoService $inscricoes)
    {
    }

    public function create(Turma $turma)
    {
        $turma->loadMissing(['oferecimento.atividade', 'oferecimento.periodos', 'vagas', 'taxas']);

        return view('inscricoes.create', [
            'turma' => $turma,
            'erros' => $this->inscricoes->validar($turma, auth()->user()),
            'perfis' => $this->inscricoes->perfisDisponiveis($turma, auth()->user()),
        ]);
    }

    public function store(InscricaoRequest $request, Turma $turma)
    {
        $matricula = $this->inscricoes->inscrever($turma, $request->user(), $request->perfil, [
            'image_use_authorization' => $request->image_use_authorization,
            'regulamento_aceito' => 'sim',
            'regulamento_ip' => $request->ip(),
        ]);

        return redirect('/minhas-inscricoes')
            ->with('alert-success', 'Inscrição realizada com sucesso!');
    }
}
