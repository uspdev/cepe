<?php

namespace App\Policies;

use App\Models\Oferecimento;
use App\Models\Turma;
use App\Models\User;

class TurmaPolicy
{
    private const PERMISSAO = 'atividades.gerenciar';

    public function viewAny(User $user): bool
    {
        return $user->gerencia(self::PERMISSAO);
    }

    /** Criação dentro de um oferecimento: vale o tipo da atividade dele. */
    public function create(User $user, Oferecimento $oferecimento): bool
    {
        return $user->gerencia(self::PERMISSAO, $oferecimento->atividade?->tipo);
    }

    public function view(User $user, Turma $turma): bool
    {
        return $user->gerencia(self::PERMISSAO, $turma->oferecimento?->atividade?->tipo);
    }

    public function update(User $user, Turma $turma): bool
    {
        return $user->gerencia(self::PERMISSAO, $turma->oferecimento?->atividade?->tipo);
    }

    public function delete(User $user, Turma $turma): bool
    {
        return $user->gerencia(self::PERMISSAO, $turma->oferecimento?->atividade?->tipo);
    }
}
