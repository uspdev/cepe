<?php

namespace Tests\Fakes;

use App\Contracts\PixGateway;
use App\Exceptions\PixIntegracaoException;
use Carbon\Carbon;

class FakePixGateway implements PixGateway
{
    public array $dadosGerados = [];

    public ?bool $resultadoIndeterminadoNaEmissao = null;

    private array $situacoes = [];

    private int $sequencia = 0;

    public function gerar(array $dados): array
    {
        if ($this->resultadoIndeterminadoNaEmissao !== null) {
            throw new PixIntegracaoException('Falha simulada na emissão.', $this->resultadoIndeterminadoNaEmissao);
        }

        $this->dadosGerados[] = $dados;
        $id = 'PIX-TEST-'.++$this->sequencia;
        $expiracao = Carbon::now('America/Sao_Paulo')->copy()->addSeconds($dados['expiracao'])->format('d/m/Y H:i:s');
        $this->situacoes[$id] = [
            'status' => 'Ativo',
            'valor' => number_format((float) $dados['valor'], 2, '.', ''),
            'dataExpiracao' => $expiracao,
        ];

        return [
            'idfpix' => $id,
            'qrCode' => '000201-Pix-copia-e-cola-'.$id,
            'qrCodeImgBase64' => base64_encode('png-'.$id),
            'dataExpiracao' => $expiracao,
        ];
    }

    public function consultar(string $id): array
    {
        if (! isset($this->situacoes[$id])) {
            throw new PixIntegracaoException('Pix não encontrado.');
        }

        return $this->situacoes[$id];
    }

    public function marcarPago(string $id): void
    {
        $this->situacoes[$id]['status'] = 'Concluído';
        $this->situacoes[$id]['dataPag'] = Carbon::now('America/Sao_Paulo')->format('d/m/Y H:i:s');
        $this->situacoes[$id]['valorEfetivamentePago'] = $this->situacoes[$id]['valor'];
    }

    public function marcarExpirado(string $id): void
    {
        $this->situacoes[$id]['status'] = 'Expirado';
    }
}
