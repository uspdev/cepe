<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaPerfil;
use App\Rules\EmailNaoUsp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CadastroRequest extends FormRequest
{
    use ValidaPerfil;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizarPerfil();
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return $this->regrasPerfil() + [
            'name' => 'required|max:255',
            'email' => ['required', 'email', 'max:255', new EmailNaoUsp, 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ];
    }

    public function attributes(): array
    {
        return $this->atributosPerfil() + ['name' => 'nome', 'password' => 'senha'];
    }

    public function messages(): array
    {
        return ['email.unique' => 'Já existe uma conta com este e-mail.'];
    }
}
