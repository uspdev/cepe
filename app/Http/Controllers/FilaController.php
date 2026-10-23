<?php

namespace App\Http\Controllers;

use App\Models\Fila;
use App\Models\Turma;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FilaController extends Controller
{
    /**
     * Recebe o pedido para o usuário entrar na fila de uma turma.
     */
    public function entrar(Request $request, Turma $turma)
    {
        $user = $request->user();

        // Se já está na fila ativo para esta turma, recupera o registro existente
        $fila = Fila::where('user_id', $user->id)
            ->where(function ($q) use ($turma) {
                $q->where('turma_id', $turma->id)
                    ->orWhereHas('vagaTurma', fn ($v) => $v->where('turma_id', $turma->id));
            })
            ->where('status', true)
            ->first();

        if (! $fila) {
            $fila = DB::transaction(function () use ($turma, $user, $request) {
                // A posição começa em 1 para a mesma turma_id, depois em 2 e assim por diante
                $maxPosicao = Fila::where(function ($q) use ($turma) {
                        $q->where('turma_id', $turma->id)
                            ->orWhereHas('vagaTurma', fn ($v) => $v->where('turma_id', $turma->id));
                    })
                    ->lockForUpdate()
                    ->max('posicao');

                $posicao = ($maxPosicao !== null ? $maxPosicao : 0) + 1;

                $vagasTurmaId = $request->input('vagas_turma_id') ?? $turma->vagas()->first()?->id;

                return Fila::create([
                    'user_id' => $user->id,
                    'turma_id' => $turma->id,
                    'vagas_turma_id' => $vagasTurmaId,
                    'posicao' => $posicao,
                    'status' => true,
                    'head' => false,
                ]);
            });
        }

        if ($request->expectsJson() || $request->ajax()) {
            $headRecord = Fila::where(function ($q) use ($turma) {
                    $q->where('turma_id', $turma->id)
                        ->orWhereHas('vagaTurma', fn ($v) => $v->where('turma_id', $turma->id));
                })
                ->where('head', true)
                ->where('status', true)
                ->first();

            $head = $headRecord ? $headRecord->posicao : (Fila::where('turma_id', $turma->id)->where('status', true)->min('posicao') ?? 1);

            return response()->json([
                'success' => true,
                'posicao' => $fila->posicao,
                'head' => $head,
                'is_head' => (bool) $fila->head,
            ]);
        }

        return redirect()->route('inscricoes.create', $turma);
    }

    /**
     * Alias para store.
     */
    public function store(Request $request, Turma $turma)
    {
        return $this->entrar($request, $turma);
    }

    /**
     * Retorna o status atual da fila para atualização via JavaScript.
     */
    public function status(Turma $turma, Request $request)
    {
        $user = $request->user();

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

        return response()->json([
            'na_fila' => (bool) $minhaFila,
            'posicao' => $minhaFila?->posicao,
            'head' => $head,
            'is_head' => $minhaFila ? (bool) $minhaFila->head : false,
            'status' => $minhaFila ? (bool) $minhaFila->status : false,
        ]);
    }
}

