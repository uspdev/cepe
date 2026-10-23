<?php

namespace App\Http\Controllers;

use App\Http\Requests\InscricaoRequest;
use App\Models\Fila;
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

        $user = auth()->user();
        $minhaFila = Fila::where('user_id', $user->id)
            ->where(function ($q) use ($turma) {
                $q->where('turma_id', $turma->id)
                    ->orWhereHas('vagaTurma', fn ($v) => $v->where('turma_id', $turma->id));
            })
            ->where('status', true)
            ->latest()
            ->first();

        $headRecord = Fila::where(function ($q) use ($turma) {
                $q->where('turma_id', $turma->id)
                    ->orWhereHas('vagaTurma', fn ($v) => $v->where('turma_id', $turma->id));
            })
            ->where('head', true)
            ->where('status', true)
            ->first();

        $head = $headRecord ? $headRecord->posicao : (Fila::where('turma_id', $turma->id)->where('status', true)->min('posicao') ?? 1);
        $posicao = $minhaFila?->posicao;
        $isHead = $minhaFila ? (bool) $minhaFila->head : false;

        return view('inscricoes.create', [
            'turma' => $turma,
            'erros' => $this->inscricoes->validar($turma, $user),
            'perfis' => $this->inscricoes->perfisDisponiveis($turma, $user),
            'minhaFila' => $minhaFila,
            'posicao' => $posicao,
            'head' => $head,
            'isHead' => $isHead,
        ]);
    }

    public function store(InscricaoRequest $request, Turma $turma)
    {
        $matricula = $this->inscricoes->inscrever($turma, $request->user(), $request->perfil, [
            'image_use_authorization' => $request->image_use_authorization,
            'regulamento_aceito' => 'sim',
            'regulamento_ip' => $request->ip(),
        ]);

        Fila::where('user_id', $request->user()->id)
            ->where(function ($q) use ($turma) {
                $q->where('turma_id', $turma->id)
                    ->orWhereHas('vagaTurma', fn ($v) => $v->where('turma_id', $turma->id));
            })
            ->where('status', true)
            ->update(['status' => false, 'head' => false]);

        return redirect('/minhas-inscricoes')
            ->with('alert-success', 'Inscrição realizada com sucesso!');
    }
}
