<?php

namespace Tests\Fakes;

use App\Contracts\BoletoGateway;
use App\Exceptions\BoletoIntegracaoException;

class FakeBoletoGateway implements BoletoGateway
{
    public array $dadosGerados = [];

    public ?bool $resultadoIndeterminadoNaEmissao = null;

    private array $situacoes = [];

    private int $sequencia = 0;

    public function gerar(array $dados): string
    {
        if ($this->resultadoIndeterminadoNaEmissao !== null) {
            throw new BoletoIntegracaoException('Falha simulada na emissão.', $this->resultadoIndeterminadoNaEmissao);
        }

        $this->dadosGerados[] = $dados;
        $codigo = 'TEST-'.++$this->sequencia;
        $this->situacoes[$codigo] = 'E';

        return $codigo;
    }

    public function situacao(string $codigo): array
    {
        if (! array_key_exists($codigo, $this->situacoes)) {
            throw new BoletoIntegracaoException('Boleto não encontrado.');
        }

        return ['situacao' => $this->situacoes[$codigo]];
    }

    public function obter(string $codigo): string
    {
        if (! array_key_exists($codigo, $this->situacoes)) {
            throw new BoletoIntegracaoException('Boleto não encontrado.');
        }

        return '%PDF-1.4 teste';
    }

    public function cancelar(string $codigo): void
    {
        if (! array_key_exists($codigo, $this->situacoes)) {
            throw new BoletoIntegracaoException('Boleto não encontrado.');
        }

        if ($this->situacoes[$codigo] === 'P') {
            throw new BoletoIntegracaoException('Boleto já pago.');
        }

        $this->situacoes[$codigo] = 'C';
    }

    public function marcarPago(string $codigo): void
    {
        $this->situacoes[$codigo] = 'P';
    }

    public function situacaoDe(string $codigo): ?string
    {
        return $this->situacoes[$codigo] ?? null;
    }
}
