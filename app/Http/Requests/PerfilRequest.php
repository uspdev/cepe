<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaPerfil;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class PerfilRequest extends FormRequest
{
    use ValidaPerfil;

    public function alvo(): User
    {
        return $this->route('user') ?? $this->user();
    }

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->alvo());
    }

    protected function prepareForValidation(): void
    {
        $this->normalizarPerfil();
    }

    public function rules(): array
    {
        $alvo = $this->alvo();
        $rules = $this->regrasPerfil($alvo->perfil?->id);

        if ($alvo->local && ($alvo->is($this->user()) || Gate::allows('admin'))) {
            $rules['name'] = 'required|max:255';
            $rules['email'] = ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($alvo->id)];
            $rules['password'] = ['nullable', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()];
        }

        if (Gate::allows('perfis.gerenciar')) {
            $rules['credito'] = 'nullable|integer|min:0';
            $rules['vinculo_usp'] = 'nullable|max:255';
            $rules['unidade_usp'] = 'nullable|max:255';
        }

        return $rules;
    }

    public function attributes(): array
    {
        return $this->atributosPerfil();
    }
}
