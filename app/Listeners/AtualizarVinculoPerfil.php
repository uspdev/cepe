<?php

namespace App\Listeners;

use App\Support\VinculoUsp;
use Uspdev\SenhaunicaSocialite\Events\SenhaunicaUsuarioLogado;

class AtualizarVinculoPerfil
{
    public function handle(SenhaunicaUsuarioLogado $evento): void
    {
        $vinculos = $evento->socialiteUser->vinculo ?? [];
        if (! $vinculos) {
            return;
        }

        $resumo = VinculoUsp::resumir($vinculos);

        $evento->user->perfil()->updateOrCreate([], [
            'vinculo_usp' => $resumo['vinculo'],
            'unidade_usp' => $resumo['unidade'],
        ]);
    }
}
