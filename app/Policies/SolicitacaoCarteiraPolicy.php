<?php

namespace App\Policies;

use App\Models\SolicitacaoCarteira;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class SolicitacaoCarteiraPolicy
{
    public function view(User $user, SolicitacaoCarteira $solicitacao): bool
    {
        return $solicitacao->solicitante_id === $user->id
            || Gate::forUser($user)->allows('carteiras.analisar');
    }

    public function analisar(User $user, SolicitacaoCarteira $solicitacao): bool
    {
        return Gate::forUser($user)->allows('carteiras.analisar');
    }

    public function reenviar(User $user, SolicitacaoCarteira $solicitacao): bool
    {
        return $solicitacao->solicitante_id === $user->id && $solicitacao->status === 'pendente';
    }
}
