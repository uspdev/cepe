<?php

namespace App\Policies;

use App\Models\Carteira;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class CarteiraPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::forUser($user)->allows('carteiras.analisar');
    }

    /** Solicitante (titular), beneficiário vinculado ou SRU. */
    public function view(User $user, Carteira $carteira): bool
    {
        return $carteira->solicitante_id === $user->id || $carteira->user_id === $user->id || $this->analisar($user, $carteira);
    }

    public function analisar(User $user, Carteira $carteira): bool
    {
        return Gate::forUser($user)->allows('carteiras.analisar');
    }

    /** Reenvio de documentos: só o solicitante, enquanto a documentação estiver pendente. */
    public function reenviar(User $user, Carteira $carteira): bool
    {
        return $carteira->solicitante_id === $user->id && $carteira->status === 'pendente';
    }
}
