<?php

namespace App\Console\Commands;

use App\Models\Atestado;
use Illuminate\Console\Command;

class VencerAtestados extends Command
{
    protected $signature = 'atestados:vencer';

    protected $description = 'Marca como vencidos os atestados aprovados com validade expirada';

    public function handle(): int
    {
        $total = Atestado::aprovados()->whereDate('valido_ate', '<', now()->toDateString())->update(['status' => 'vencido']);
        $this->info("{$total} atestado(s) marcados como vencidos.");

        return self::SUCCESS;
    }
}
