<?php

namespace App\Listeners;

use App\Support\VinculoUsp;
use Uspdev\SenhaunicaSocialite\Events\SenhaunicaUsuarioLogado;

class AtualizarVinculoPerfil
{
    public function handle(SenhaunicaUsuarioLogado $evento): void
    {
        $vinculos = $evento->socialiteUser->vinculo ?? [];
        $perfil = $evento->user->perfil;

        if (! $vinculos) {
            // Revalidação: sem vínculo na Senha Única, zera os códigos (base de descontos e alumni);
            // o rótulo e a unidade só são limpos se não foram definidos por admin
            $perfil?->update($perfil->vinculo_manual
                ? ['vinculos_usp' => []]
                : ['vinculo_usp' => null, 'unidade_usp' => null, 'vinculos_usp' => []]);

            return;
        }

        $dados = ['vinculos_usp' => VinculoUsp::codigos($vinculos)];

        if (! $perfil?->vinculo_manual) {
            $resumo = VinculoUsp::resumir($vinculos);
            $dados += ['vinculo_usp' => $resumo['vinculo'], 'unidade_usp' => $resumo['unidade']];
        }

        $evento->user->perfil()->updateOrCreate([], $dados);
    }
}
