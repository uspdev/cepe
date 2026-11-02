<?php

namespace App\Policies;

use App\Models\Atividade;
use App\Models\Oferecimento;
use App\Models\User;

class OferecimentoPolicy
{
    private const PERMISSAO = 'atividades.gerenciar';

    public function viewAny(User $user): bool
    {
        return $user->gerencia(self::PERMISSAO);
    }

    /** Criação dentro de uma atividade: vale o tipo dela. */
    public function create(User $user, Atividade $atividade): bool
    {
        return $user->gerencia(self::PERMISSAO, $atividade->tipo);
    }

    public function view(User $user, Oferecimento $oferecimento): bool
    {
        return $user->gerencia(self::PERMISSAO, $oferecimento->atividade?->tipo);
    }

    public function update(User $user, Oferecimento $oferecimento): bool
    {
        return $user->gerencia(self::PERMISSAO, $oferecimento->atividade?->tipo);
    }

    public function delete(User $user, Oferecimento $oferecimento): bool
    {
        return $user->gerencia(self::PERMISSAO, $oferecimento->atividade?->tipo);
    }
}
