<?php

namespace App\Policies;

use App\Models\Matricula;
use App\Models\User;

class MatriculaPolicy
{
    /** O próprio aluno ou quem gerencia inscrições do grupo da atividade (sru e gestores). */
    public function cancel(User $user, Matricula $matricula): bool
    {
        return $matricula->user_id === $user->id
            || $user->gerencia('inscricoes.gerenciar', $matricula->turma?->oferecimento?->atividade?->tipo);
    }
}
