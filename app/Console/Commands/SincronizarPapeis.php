<?php

namespace App\Console\Commands;

use App\Support\Papeis;
use Illuminate\Console\Command;

class SincronizarPapeis extends Command
{
    protected $signature = 'cepe:papeis';

    protected $description = 'Cria/atualiza as roles e permissions da aplicação definidas em config/cepe.php';

    public function handle(): int
    {
        foreach (Papeis::sincronizar() as $papel => $permissoes) {
            $this->line("{$papel}: ".($permissoes ? implode(', ', $permissoes) : '(sem permissions)'));
        }
        $this->info('Papéis sincronizados.');

        return self::SUCCESS;
    }
}
