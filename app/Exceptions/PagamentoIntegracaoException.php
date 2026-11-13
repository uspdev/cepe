<?php

namespace App\Exceptions;

use RuntimeException;

class PagamentoIntegracaoException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $resultadoIndeterminado = false)
    {
        parent::__construct($message);
    }
}
