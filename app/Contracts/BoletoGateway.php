<?php

namespace App\Contracts;

interface BoletoGateway
{
    public function gerar(array $dados): string;

    public function situacao(string $codigo): array;

    public function obter(string $codigo): string;

    public function cancelar(string $codigo): void;
}
