<?php

namespace App\Policies;

use App\Models\Atestado;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AtestadoPolicy
{
    public function view(User $user, Atestado $atestado): bool
    {
        return $atestado->user_id === $user->id || Gate::forUser($user)->allows('admin');
    }
}
