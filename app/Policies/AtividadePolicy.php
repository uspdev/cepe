<?php

namespace App\Policies;

use App\Models\Atividade;
use App\Models\User;

class AtividadePolicy
{
    private const PERMISSAO = 'atividades.gerenciar';

    public function viewAny(User $user): bool
    {
        return $user->gerencia(self::PERMISSAO);
    }

    public function create(User $user): bool
    {
        return $user->gerencia(self::PERMISSAO);
    }

    public function view(User $user, Atividade $atividade): bool
    {
        return $user->gerencia(self::PERMISSAO, $atividade->tipo);
    }

    public function update(User $user, Atividade $atividade): bool
    {
        return $user->gerencia(self::PERMISSAO, $atividade->tipo);
    }

    public function delete(User $user, Atividade $atividade): bool
    {
        return $user->gerencia(self::PERMISSAO, $atividade->tipo);
    }
}
