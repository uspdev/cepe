<?php

namespace App\Policies;

use App\Models\Atestado;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AtestadoPolicy
{
    /** Listagem de análise: quem analisa ao menos um tipo de documento. */
    public function viewAny(User $user): bool
    {
        return $user->tiposAtestadoAnalisaveis() !== [];
    }

    public function view(User $user, Atestado $atestado): bool
    {
        return $atestado->user_id === $user->id || $this->analisar($user, $atestado);
    }

    /** Análise, consulta e remoção: a permission depende do tipo do documento. */
    public function analisar(User $user, Atestado $atestado): bool
    {
        $permissao = config('cepe.atestados_permissoes.'.$atestado->tipo);

        return $permissao && Gate::forUser($user)->allows($permissao);
    }
}
