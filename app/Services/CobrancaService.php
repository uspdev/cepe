<?php

namespace App\Services;

use App\Contracts\BoletoGateway;
use App\Contracts\PixGateway;
use App\Exceptions\BoletoIntegracaoException;
use App\Exceptions\PixIntegracaoException;
use App\Models\Cobranca;
use App\Models\Matricula;
use App\Models\Perfil;
use App\Rules\Cpf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CobrancaService
{
    public function __construct(private BoletoGateway $boletoGateway, private PixGateway $pixGateway) {}

    public function emitirPrimeira(Matricula $matricula, string $metodo = Cobranca::BOLETO, int $parcelas = 1): Cobranca
    {
        $matricula->loadMissing(['user.perfil', 'turma.oferecimento.atividade']);
        $tipo = $matricula->turma->oferecimento->atividade->tipo;

        if (! self::parcelamentoDisponivel($tipo, $metodo, (int) $matricula->valor)) {
            $parcelas = 1;
        } elseif (! in_array($parcelas, [1, 2, 3], true)) {
            throw ValidationException::withMessages(['parcelas' => 'Selecione uma quantidade de parcelas válida.']);
        }

        return $this->emitir($matricula, 1, $parcelas, metodo: $metodo);
    }

    public static function parcelamentoDisponivel(string $tipo, string $metodo, int $valorReais): bool
    {
        return $metodo === Cobranca::BOLETO
            && in_array($tipo, config('cepe.grupos_catalogo.cursos'), true)
            && $valorReais >= (int) config('cepe.pagamentos.parcelamento_cursos.valor_minimo', 300);
    }

    public function emitirProxima(Cobranca $anterior): ?Cobranca
    {
        $anterior->loadMissing('matricula.user.perfil', 'matricula.turma.oferecimento.atividade');
        $matricula = $anterior->matricula;
        $proxima = $anterior->parcela + 1;

        if ($anterior->metodo !== Cobranca::BOLETO
            || $anterior->status !== Cobranca::PAGA
            || $proxima > $anterior->total_parcelas
            || $matricula->status !== Matricula::MATRICULADA) {
            return null;
        }

        $existente = Cobranca::where('matricula_id', $matricula->id)->where('parcela', $proxima)->first();

        return $this->emitir($matricula, $proxima, $anterior->total_parcelas, $existente, $anterior, $anterior->metodo);
    }

    public function emitirNovamente(Cobranca $cobranca): ?Cobranca
    {
        if ($cobranca->status !== Cobranca::FALHA_EMISSAO || $cobranca->tentativas_emissao >= 3) {
            return null;
        }

        return $this->emitir(
            $cobranca->matricula()->with(['user.perfil', 'turma.oferecimento.atividade'])->firstOrFail(),
            $cobranca->parcela,
            $cobranca->total_parcelas,
            $cobranca,
            metodo: $cobranca->metodo,
        );
    }

    public function sincronizar(Cobranca $cobranca): void
    {
        $cobranca->refresh();
        $codigoExterno = $cobranca->metodo === Cobranca::PIX ? $cobranca->id_pix : $cobranca->codigo_boleto;
        if (! in_array($cobranca->status, [
            Cobranca::EMITIDA,
            Cobranca::CANCELAMENTO_PENDENTE,
            Cobranca::VENCIDA,
            Cobranca::CANCELADA,
        ], true) || ! $codigoExterno) {
            return;
        }

        try {
            $situacao = $cobranca->metodo === Cobranca::PIX
                ? $this->pixGateway->consultar($codigoExterno)
                : $this->boletoGateway->situacao($codigoExterno);
        } catch (BoletoIntegracaoException|PixIntegracaoException $e) {
            $cobranca->update(['consultado_em' => now()]);
            Log::warning('Falha ao consultar cobrança.', [
                'cobranca_id' => $cobranca->id,
                'exception' => $e::class,
            ]);

            return;
        }

        $statusBanco = $this->statusRemoto($cobranca, $situacao);
        if ($statusBanco === 'P') {
            try {
                $this->registrarPagamento($cobranca, $this->normalizarPagamento($cobranca, $situacao));
            } catch (BoletoIntegracaoException|PixIntegracaoException) {
            }

            return;
        }

        if ($statusBanco === 'C' || $statusBanco === 'X') {
            $cobranca->update([
                'status' => $statusBanco === 'X' ? Cobranca::VENCIDA : Cobranca::CANCELADA,
                'consultado_em' => now(),
                'cancelado_em' => $statusBanco === 'C' ? ($cobranca->cancelado_em ?? now()) : $cobranca->cancelado_em,
            ]);
            $this->cancelarPorVencimento($cobranca->matricula()->firstOrFail());

            return;
        }

        $cobranca->update(['consultado_em' => now()]);
        $expirada = $cobranca->metodo === Cobranca::PIX
            ? $cobranca->expira_em?->lessThanOrEqualTo(Carbon::now(config('cepe.fuso_frequencia')))
            : $cobranca->vencimento->lt(Carbon::today(config('cepe.fuso_frequencia')));
        if ($expirada) {
            $cobranca->update(['status' => Cobranca::VENCIDA]);
            $this->cancelarPorVencimento($cobranca->matricula()->firstOrFail());
        } elseif ($cobranca->status === Cobranca::CANCELAMENTO_PENDENTE) {
            $this->tentarCancelar($cobranca);
        }
    }

    public function cancelarMatricula(Matricula $matricula): void
    {
        foreach ($matricula->cobrancas()->whereIn('status', [
            Cobranca::EMITIDA,
            Cobranca::CANCELAMENTO_PENDENTE,
        ])->get() as $cobranca) {
            $this->tentarCancelar($cobranca);
        }

        DB::transaction(function () use ($matricula) {
            $matricula = Matricula::whereKey($matricula->id)->lockForUpdate()->firstOrFail();
            if ($matricula->status === Matricula::CANCELADA) {
                return;
            }

            $houvePagamento = $matricula->cobrancas()->where('status', Cobranca::PAGA)->exists();
            $pagamentoIncerto = $matricula->cobrancas()->whereIn('status', [
                Cobranca::EMITINDO,
                Cobranca::CANCELAMENTO_PENDENTE,
                Cobranca::EMISSAO_INDETERMINADA,
            ])->exists();
            $requerRevisao = ($houvePagamento || $pagamentoIncerto) && $matricula->credito_usado != 0;
            $matricula->update([
                'status' => Matricula::CANCELADA,
                'revisao_credito_sru' => $requerRevisao,
                'revisao_credito_sru_em' => $requerRevisao ? now() : null,
            ]);

            if (! $houvePagamento && ! $pagamentoIncerto && $matricula->credito_usado != 0) {
                $this->estornarCredito($matricula);
            }
        });
    }

    public function cancelarMatriculaSemPagamento(Matricula $matricula): void
    {
        DB::transaction(function () use ($matricula) {
            $matricula = Matricula::whereKey($matricula->id)->lockForUpdate()->firstOrFail();
            if ($matricula->status === Matricula::CANCELADA
                || $matricula->cobrancas()->where('status', Cobranca::PAGA)->exists()) {
                return;
            }

            $matricula->update([
                'status' => Matricula::CANCELADA,
                'revisao_credito_sru' => false,
                'revisao_credito_sru_em' => null,
            ]);
            if ($matricula->credito_usado != 0) {
                $this->estornarCredito($matricula);
            }
        });
    }

    public function obterPdf(Cobranca $cobranca): string
    {
        if ($cobranca->metodo !== Cobranca::BOLETO
            || ! $cobranca->codigo_boleto
            || in_array($cobranca->status, [Cobranca::FALHA_EMISSAO, Cobranca::EMISSAO_INDETERMINADA], true)) {
            abort(404);
        }

        return $this->boletoGateway->obter($cobranca->codigo_boleto);
    }

    private function emitir(
        Matricula $matricula,
        int $parcela,
        int $total,
        ?Cobranca $existente = null,
        ?Cobranca $anterior = null,
        string $metodo = Cobranca::BOLETO,
    ): Cobranca {
        $matricula->loadMissing(['user.perfil', 'turma.oferecimento.atividade']);

        [$cobranca, $podeEmitir] = DB::transaction(function () use ($matricula, $parcela, $total, $existente, $anterior, $metodo) {
            $matricula = Matricula::whereKey($matricula->id)->lockForUpdate()->firstOrFail();
            if (in_array($matricula->status, [Matricula::CANCELADA], true)) {
                throw ValidationException::withMessages(['inscricao' => 'A matrícula foi cancelada e não pode gerar cobrança.']);
            }

            $cobranca = Cobranca::where('matricula_id', $matricula->id)
                ->where('parcela', $parcela)
                ->lockForUpdate()
                ->first();
            if (! $cobranca && $existente) {
                $cobranca = Cobranca::whereKey($existente->id)->lockForUpdate()->firstOrFail();
            }

            if (! $cobranca) {
                $cpf = $anterior?->cpf_cnpj ?? preg_replace('/\D/', '', (string) $matricula->user->perfilOuNovo()->cpf);
                $nome = $anterior?->nome_sacado ?? $matricula->user->name;
                $email = $anterior?->email_sacado ?? $matricula->user->email;
                if (! Cpf::valido((string) $cpf) || blank($email)) {
                    throw new BoletoIntegracaoException('O perfil precisa ter CPF e e-mail válidos para emitir o boleto.');
                }
                $expiraEm = $metodo === Cobranca::PIX
                    ? Carbon::now(config('cepe.fuso_frequencia'))
                        ->copy()
                        ->addSeconds((int) config('cepe.pagamentos.pix.expiracao_segundos', 10800))
                        ->setTimezone(config('app.timezone'))
                    : null;

                $cobranca = Cobranca::create([
                    'matricula_id' => $matricula->id,
                    'metodo' => $metodo,
                    'parcela' => $parcela,
                    'total_parcelas' => $total,
                    'valor_centavos' => $this->valorParcelaCentavos((int) $matricula->valor, $parcela, $total),
                    'vencimento' => $expiraEm?->copy()->setTimezone(config('cepe.fuso_frequencia'))->toDateString()
                        ?? $this->vencimento($matricula, $parcela, $anterior),
                    'expira_em' => $expiraEm,
                    'nome_sacado' => $nome,
                    'cpf_cnpj' => $cpf,
                    'email_sacado' => $email,
                    'status' => Cobranca::EMITINDO,
                    'tentativas_emissao' => 1,
                ]);

                return [$cobranca, true];
            }

            if ($cobranca->codigo_boleto
                || $cobranca->status !== Cobranca::FALHA_EMISSAO
                || $cobranca->tentativas_emissao >= 3) {
                return [$cobranca, false];
            }

            $cobranca->update([
                'status' => Cobranca::EMITINDO,
                'tentativas_emissao' => $cobranca->tentativas_emissao + 1,
                'erro_codigo' => null,
            ]);

            return [$cobranca->fresh(), true];
        });

        if (! $podeEmitir) {
            return $cobranca;
        }

        try {
            if ($metodo === Cobranca::PIX) {
                $pix = $this->pixGateway->gerar($this->dadosPix($matricula, $cobranca));
                $expiraEm = $this->dataExpiracaoPix($pix['dataExpiracao'] ?? null, $cobranca);
                $cobranca->update([
                    'id_pix' => $pix['idfpix'],
                    'pix_copia_cola' => $pix['qrCode'] ?? null,
                    'pix_qr_code_img_base64' => $pix['qrCodeImgBase64'] ?? null,
                    'expira_em' => $expiraEm,
                    'vencimento' => $expiraEm->copy()->setTimezone(config('cepe.fuso_frequencia'))->toDateString(),
                    'status' => Cobranca::EMITIDA,
                    'emitido_em' => now(),
                ]);
            } else {
                $codigo = $this->boletoGateway->gerar($this->dadosBoleto($matricula, $cobranca));
                $cobranca->update([
                    'codigo_boleto' => $codigo,
                    'status' => Cobranca::EMITIDA,
                    'emitido_em' => now(),
                ]);
            }
        } catch (BoletoIntegracaoException|PixIntegracaoException $e) {
            $cobranca->update([
                'status' => $e->resultadoIndeterminado ? Cobranca::EMISSAO_INDETERMINADA : Cobranca::FALHA_EMISSAO,
                'erro_codigo' => $e->resultadoIndeterminado ? 'resultado_indeterminado' : 'emissao_recusada',
            ]);
            Log::warning('Falha na emissão de cobrança.', [
                'cobranca_id' => $cobranca->id,
                'exception' => $e::class,
                'resultado_indeterminado' => $e->resultadoIndeterminado,
            ]);

            throw $e;
        }

        $cobranca = $cobranca->fresh();
        if ($cobranca->matricula()->value('status') === Matricula::CANCELADA) {
            $this->tentarCancelar($cobranca);
        }

        return $cobranca->fresh();
    }

    private function dadosBoleto(Matricula $matricula, Cobranca $cobranca): array
    {
        $matricula->loadMissing(['user.perfil', 'turma.oferecimento.atividade']);
        $atividade = $matricula->turma->oferecimento->atividade;
        $grupo = in_array($atividade->tipo, config('cepe.grupos_catalogo.cursos'), true)
            ? 'cursos'
            : (in_array($atividade->tipo, config('cepe.grupos_catalogo.eventos'), true) ? 'eventos' : 'default');
        $configuracao = config('cepe.pagamentos.'.$grupo);

        return [
            'codigoUnidadeDespesa' => config('cepe.pagamentos.codigoUnidadeDespesa'),
            'codigoFonteRecurso' => $configuracao['codigoFonteRecurso'],
            'nomeFonte' => $configuracao['nomeFonte'],
            'nomeSubfonte' => $configuracao['nomeSubfonte'],
            'estruturaHierarquica' => $configuracao['estruturaHierarquica'],
            'codigoConvenio' => '',
            'dataVencimentoBoleto' => $cobranca->vencimento->format('d/m/Y'),
            'valorDocumento' => number_format($cobranca->valor_centavos / 100, 2, '.', ''),
            'valorDesconto' => 0,
            'tipoSacado' => 'PF',
            'cpfCnpj' => $cobranca->cpf_cnpj,
            'nomeSacado' => $cobranca->nome_sacado,
            'codigoEmail' => $cobranca->email_sacado,
            'informacoesBoletoSacado' => sprintf(
                'Taxa de inscrição em %s. Parcela %d de %d.',
                $atividade->nome,
                $cobranca->parcela,
                $cobranca->total_parcelas,
            ),
            'instrucoesObjetoCobranca' => 'Não reembolsável em nenhuma hipótese. Pagável em qualquer banco via internet ou agência até a data de vencimento. Não receber após o vencimento.',
        ];
    }

    private function dadosPix(Matricula $matricula, Cobranca $cobranca): array
    {
        $matricula->loadMissing('turma.oferecimento.atividade');
        $atividade = $matricula->turma->oferecimento->atividade;
        $grupo = in_array($atividade->tipo, config('cepe.grupos_catalogo.cursos'), true)
            ? 'cursos'
            : (in_array($atividade->tipo, config('cepe.grupos_catalogo.eventos'), true) ? 'eventos' : 'default');
        $configuracao = config('cepe.pagamentos.'.$grupo);

        return [
            'tipoPessoa' => 'PF',
            'valor' => $cobranca->valor_centavos / 100,
            'infoCobranca' => sprintf(
                'Taxa de inscrição em %s. Parcela %d de %d.',
                $atividade->nome,
                $cobranca->parcela,
                $cobranca->total_parcelas,
            ),
            'emailPesOrg' => $cobranca->email_sacado,
            'codigoFonteRecurso' => $configuracao['codigoFonteRecurso'],
            'codigoUnidadeDespesa' => config('cepe.pagamentos.codigoUnidadeDespesa'),
            'estruturaHierarquica' => $configuracao['estruturaHierarquica'],
            'expiracao' => (int) config('cepe.pagamentos.pix.expiracao_segundos', 10800),
            'docPesOrg' => $cobranca->cpf_cnpj,
            'nomePesOrg' => $cobranca->nome_sacado,
        ];
    }

    private function statusRemoto(Cobranca $cobranca, array $situacao): string
    {
        if ($cobranca->metodo === Cobranca::BOLETO) {
            return strtoupper((string) ($situacao['situacao'] ?? ''));
        }

        return match (Str::ascii(mb_strtolower(trim((string) ($situacao['status'] ?? ''))))) {
            'concluido', 'pago', 'paid' => 'P',
            'expirado', 'expired' => 'X',
            'cancelado', 'cancelada', 'canceled', 'cancelled' => 'C',
            default => 'A',
        };
    }

    private function normalizarPagamento(Cobranca $cobranca, array $situacao): array
    {
        if ($cobranca->metodo === Cobranca::PIX) {
            $situacao['valorEfetivamentePago'] = $situacao['valorEfetivamentePago'] ?? $situacao['valorPago'] ?? $situacao['valor'] ?? null;
            $situacao['dataEfetivaPagamento'] = $situacao['dataEfetivaPagamento'] ?? $situacao['dataPag'] ?? $situacao['dataPagamento'] ?? null;
        }

        return $situacao;
    }

    private function dataExpiracaoPix(?string $data, Cobranca $cobranca): Carbon
    {
        foreach (['d/m/Y H:i:s', 'Y-m-d H:i:s', 'Y-m-d\TH:i:sP', 'Y-m-d\TH:i:s.uP'] as $formato) {
            if (! $data) {
                break;
            }

            try {
                $expiracao = Carbon::createFromFormat($formato, $data, config('cepe.fuso_frequencia'));
                if ($expiracao) {
                    return $expiracao->setTimezone(config('app.timezone'));
                }
            } catch (\Throwable) {
            }
        }

        if ($data) {
            try {
                return Carbon::parse($data, config('cepe.fuso_frequencia'))->setTimezone(config('app.timezone'));
            } catch (\Throwable) {
            }
        }

        return $cobranca->expira_em
            ?? Carbon::now(config('cepe.fuso_frequencia'))
                ->copy()
                ->addSeconds((int) config('cepe.pagamentos.pix.expiracao_segundos', 10800))
                ->setTimezone(config('app.timezone'));
    }

    private function valorParcelaCentavos(int $valorReais, int $parcela, int $total): int
    {
        $totalCentavos = $valorReais * 100;
        $base = intdiv($totalCentavos, $total);
        $resto = $totalCentavos % $total;

        return $base + ($parcela <= $resto ? 1 : 0);
    }

    private function vencimento(Matricula $matricula, int $parcela, ?Cobranca $anterior): Carbon
    {
        if ($parcela > 1 && $anterior) {
            $dataPrevista = $anterior->vencimento->copy()->addMonthNoOverflow();
        } else {
            $tipo = $matricula->turma->oferecimento->atividade->tipo;
            $grupo = in_array($tipo, config('cepe.grupos_catalogo.cursos'), true)
                ? 'cursos'
                : (in_array($tipo, config('cepe.grupos_catalogo.eventos'), true) ? 'eventos' : 'default');
            $dias = (int) config('cepe.pagamentos.'.$grupo.'.vencimento', config('cepe.pagamentos.default.vencimento', 3));
            $dataPrevista = Carbon::today(config('cepe.fuso_frequencia'))->addDays($dias);
        }

        $hoje = Carbon::today(config('cepe.fuso_frequencia'));

        return $dataPrevista->greaterThan($hoje) ? $dataPrevista : $hoje->addDay();
    }

    private function registrarPagamento(Cobranca $cobranca, array $situacao): void
    {
        $deveEmitirProxima = DB::transaction(function () use ($cobranca, $situacao) {
            $cobranca = Cobranca::whereKey($cobranca->id)->lockForUpdate()->firstOrFail();
            if ($cobranca->status === Cobranca::PAGA) {
                return false;
            }

            if (! in_array($cobranca->status, [
                Cobranca::EMITIDA,
                Cobranca::CANCELAMENTO_PENDENTE,
                Cobranca::VENCIDA,
                Cobranca::CANCELADA,
            ], true)) {
                return false;
            }

            $cobranca->update([
                'status' => Cobranca::PAGA,
                'pago_em' => $this->dataPagamento($situacao),
                'consultado_em' => now(),
                'valor_pago_centavos' => $this->valorPagoCentavos($situacao['valorEfetivamentePago'] ?? null) ?? $cobranca->valor_centavos,
            ]);

            $matricula = Matricula::whereKey($cobranca->matricula_id)->lockForUpdate()->firstOrFail();
            if ($cobranca->parcela === 1 && $matricula->status === Matricula::AGUARDANDO_PAGAMENTO) {
                $matricula->update(['status' => Matricula::MATRICULADA]);
            } elseif ($matricula->status === Matricula::CANCELADA && $matricula->credito_usado != 0) {
                $matricula->update([
                    'revisao_credito_sru' => true,
                    'revisao_credito_sru_em' => now(),
                ]);
            }

            return $matricula->status === Matricula::MATRICULADA
                && $cobranca->parcela < $cobranca->total_parcelas;
        });

        if ($deveEmitirProxima) {
            $this->emitirProxima($cobranca->fresh());
        }
    }

    private function cancelarPorVencimento(Matricula $matricula): void
    {
        $this->cancelarMatricula($matricula);
    }

    private function tentarCancelar(Cobranca $cobranca): void
    {
        if (in_array($cobranca->status, [Cobranca::PAGA, Cobranca::CANCELADA, Cobranca::VENCIDA], true)) {
            return;
        }

        if ($cobranca->metodo === Cobranca::PIX) {
            $cobranca->update([
                'status' => Cobranca::CANCELADA,
                'cancelado_em' => now(),
                'consultado_em' => now(),
            ]);

            return;
        }

        if (! $cobranca->codigo_boleto) {
            return;
        }

        try {
            $this->boletoGateway->cancelar($cobranca->codigo_boleto);
            $cobranca->update([
                'status' => Cobranca::CANCELADA,
                'cancelado_em' => now(),
                'consultado_em' => now(),
            ]);
        } catch (BoletoIntegracaoException) {
            $cobranca->update([
                'status' => Cobranca::CANCELAMENTO_PENDENTE,
                'consultado_em' => now(),
            ]);
            Log::warning('Cancelamento de boleto pendente de confirmação.', ['cobranca_id' => $cobranca->id]);
        }
    }

    private function estornarCredito(Matricula $matricula): void
    {
        $perfil = Perfil::where('user_id', $matricula->user_id)->lockForUpdate()->first();
        $perfil?->update(['credito' => max(0, $perfil->credito + $matricula->credito_usado)]);
    }

    private function valorPagoCentavos(mixed $valor): ?int
    {
        $valor = trim(str_replace(',', '.', (string) $valor));
        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $valor, $partes)) {
            return null;
        }

        return ((int) $partes[1] * 100) + (int) str_pad($partes[2] ?? '', 2, '0');
    }

    private function dataPagamento(array $situacao): Carbon
    {
        $data = $situacao['dataEfetivaPagamento'] ?? null;
        foreach (['d/m/Y H:i:s', 'Y-m-d H:i:s', 'd/m/Y', 'Y-m-d'] as $formato) {
            if (! $data) {
                break;
            }

            try {
                $dataConvertida = Carbon::createFromFormat($formato, $data, config('cepe.fuso_frequencia'));
                if ($dataConvertida) {
                    return $dataConvertida;
                }
            } catch (\Throwable) {
            }
        }

        return Carbon::now(config('cepe.fuso_frequencia'));
    }
}
