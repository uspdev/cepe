<?php

namespace App\Console\Commands;

use App\Exceptions\BoletoIntegracaoException;
use App\Models\Cobranca;
use App\Models\Matricula;
use App\Services\CobrancaService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SincronizarBoletos extends Command
{
    protected $signature = 'boletos:sincronizar';

    protected $description = 'Consulta boletos pendentes e atualiza matrículas';

    public function handle(CobrancaService $cobrancas): int
    {
        Cobranca::where('metodo', Cobranca::BOLETO)
            ->where('status', Cobranca::EMITINDO)
            ->where('created_at', '<=', now()->subMinutes(15))
            ->update([
                'status' => Cobranca::EMISSAO_INDETERMINADA,
                'erro_codigo' => 'emissao_sem_resposta',
            ]);

        $lote = max(1, (int) config('cepe.pagamentos.consulta.lote', 100));
        Cobranca::where('metodo', Cobranca::BOLETO)
            ->where('status', Cobranca::FALHA_EMISSAO)
            ->where('tentativas_emissao', '<', 3)
            ->whereHas('matricula', fn ($q) => $q->where('status', Matricula::MATRICULADA))
            ->orderBy('id')
            ->limit($lote)
            ->get()
            ->each(function (Cobranca $cobranca) use ($cobrancas) {
                try {
                    $cobrancas->emitirNovamente($cobranca);
                } catch (BoletoIntegracaoException) {
                }
            });

        Cobranca::where('metodo', Cobranca::BOLETO)
            ->where(function ($query) {
                $query->whereIn('status', [Cobranca::EMITIDA, Cobranca::CANCELAMENTO_PENDENTE])
                    ->orWhere(function ($query) {
                        $dias = (int) config('cepe.pagamentos.consulta.reconciliar_canceladas_dias', 7);
                        $query->where('status', Cobranca::VENCIDA)
                            ->whereDate('vencimento', '>=', Carbon::today(config('cepe.fuso_frequencia'))->subDays($dias));
                    })
                    ->orWhere(function ($query) {
                        $query->where('status', Cobranca::CANCELADA)
                            ->where('cancelado_em', '>=', now()->subDays((int) config('cepe.pagamentos.consulta.reconciliar_canceladas_dias', 7)));
                    });
            })
            ->whereNotNull('codigo_boleto')
            ->orderBy('consultado_em')
            ->limit($lote)
            ->get()
            ->each(fn (Cobranca $cobranca) => $cobrancas->sincronizar($cobranca));

        return self::SUCCESS;
    }
}
