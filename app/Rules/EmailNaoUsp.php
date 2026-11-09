<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EmailNaoUsp implements ValidationRule
{
    public static function usp(string $email): bool
    {
        $dominio = mb_strtolower(trim(substr(strrchr($email, '@') ?: '', 1)));

        return $dominio === 'usp.br' || str_ends_with($dominio, '.usp.br');
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (self::usp((string) $value)) {
            $fail('E-mails USP devem entrar com a Senha Única.');
        }
    }
}
