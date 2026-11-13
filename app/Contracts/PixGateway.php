<?php

namespace App\Contracts;

interface PixGateway
{
    public function gerar(array $dados): array;

    public function consultar(string $id): array;
}
