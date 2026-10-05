<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

class PerfilPolicy
{
    public function update(User $ator, User $alvo): bool
    {
        return $ator->is($alvo) || Gate::forUser($ator)->allows('admin');
    }
}
