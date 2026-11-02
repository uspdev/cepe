<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class Papeis
{
    public const GUARD = 'web';

    /**
     * Cria (ou atualiza) as permissions e roles de `config('cepe')` no guard `web`,
     * o mesmo usado pela tela de usuários da senhaunica-socialite. Idempotente.
     *
     * @return array<string, string[]> permissions de cada role
     */
    public static function sincronizar(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (config('cepe.permissoes') as $permissao) {
            Permission::findOrCreate($permissao, self::GUARD);
        }

        // Seeders com WithoutModelEvents não disparam o evento que limpa o cache do Spatie:
        // sem este reset a lista (vazia) lida no 1º findOrCreate continuaria valendo e as roles não achariam as permissions.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $papeis = config('cepe.papeis');
        foreach ($papeis as $nome => $permissoes) {
            Role::findOrCreate($nome, self::GUARD)->syncPermissions($permissoes);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $papeis;
    }
}
