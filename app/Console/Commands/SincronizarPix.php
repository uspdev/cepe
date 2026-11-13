<?php

namespace App\Console\Commands;

use App\Models\Cobranca;
use App\Services\CobrancaService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SincronizarPix extends Command
{
    protected $signature = 'pix:sincronizar';

    protected $description = 'Consulta Pix pendentes e atualiza matrículas';

    public function handle(CobrancaService $cobrancas): int
    {
        Cobranca::where('metodo', Cobranca::PIX)
            ->where('status', Cobranca::EMITINDO)
            ->where('created_at', '<=', now()->subMinutes(15))
            ->update([
                'status' => Cobranca::EMISSAO_INDETERMINADA,
                'erro_codigo' => 'emissao_sem_resposta',
            ]);

        $lote = max(1, (int) config('cepe.pagamentos.pix.consulta_lote', 30));
        $dias = (int) config('cepe.pagamentos.consulta.reconciliar_canceladas_dias', 7);

        Cobranca::where('metodo', Cobranca::PIX)
            ->where(function ($query) use ($dias) {
                $query->whereIn('status', [Cobranca::EMITIDA, Cobranca::CANCELAMENTO_PENDENTE])
                    ->orWhere(fn ($q) => $q->where('status', Cobranca::VENCIDA)
                        ->whereDate('vencimento', '>=', Carbon::today(config('cepe.fuso_frequencia'))->subDays($dias)))
                    ->orWhere(fn ($q) => $q->where('status', Cobranca::CANCELADA)
                        ->where('cancelado_em', '>=', now()->subDays($dias)));
            })
            ->whereNotNull('id_pix')
            ->orderBy('consultado_em')
            ->limit($lote)
            ->get()
            ->each(fn (Cobranca $cobranca) => $cobrancas->sincronizar($cobranca));

        return self::SUCCESS;
    }
}
