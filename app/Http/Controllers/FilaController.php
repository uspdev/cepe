<?php

namespace App\Http\Controllers;

use App\Models\Turma;
use App\Services\InscricaoService;
use Illuminate\Http\Request;

class FilaController extends Controller
{
    public function __construct(private InscricaoService $inscricoes)
    {
    }

    /**
     * Confirma a entrada na fila da turma, com o perfil escolhido (idempotente).
     */
    public function entrar(Request $request, Turma $turma)
    {
        $fila = $this->inscricoes->entrarNaFila($turma, $request->user(), $request->input('perfil'));

        if ($request->expectsJson() || $request->ajax()) {
            return $this->statusJson($turma, $request->user(), $fila);
        }

        return redirect()->route('inscricoes.create', $turma);
    }

    /**
     * Status da fila para o polling do JavaScript; também serve de heartbeat —
     * o touch mantém a entrada do usuário ativa enquanto ele estiver na página.
     */
    public function status(Turma $turma, Request $request)
    {
        $fila = $this->inscricoes->filaAtiva($turma, $request->user());
        $fila?->touch();

        return $this->statusJson($turma, $request->user(), $fila);
    }

    private function statusJson(Turma $turma, $user, $fila)
    {
        return response()->json([
            'na_fila' => (bool) $fila,
            'posicao' => $fila?->posicao,
            'head' => $this->inscricoes->filaHead($turma),
            'is_head' => $this->inscricoes->isVez($turma, $user, $fila),
        ]);
    }
}
