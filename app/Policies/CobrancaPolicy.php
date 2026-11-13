<?php

namespace App\Policies;

use App\Models\Cobranca;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class CobrancaPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::forUser($user)->allows('pagamentos.gerenciar');
    }

    public function view(User $user, Cobranca $cobranca): bool
    {
        return $cobranca->matricula?->user_id === $user->id
            || Gate::forUser($user)->allows('pagamentos.gerenciar');
    }
}
