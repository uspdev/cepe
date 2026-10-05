<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class PerfilRequest extends FormRequest
{
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
        $digitos = fn ($v) => is_string($v) ? preg_replace('/\D/', '', $v) : $v;
        $dados = [];
        foreach (['cpf', 'telefone', 'celular', 'emergencia_telefone', 'cep'] as $campo) {
            if ($this->has($campo)) {
                $dados[$campo] = $digitos($this->input($campo));
            }
        }
        $this->merge($dados + [
            'sem_cpf' => $this->boolean('sem_cpf'),
            'sem_rg' => $this->boolean('sem_rg'),
        ]);
    }

    public function rules(): array
    {
        $alvo = $this->alvo();
        $perfilId = $alvo->perfil?->id;

        $rules = [
            'sexo' => ['required', Rule::in(['m', 'f'])],
            'data_nascimento' => 'required|date_format:d/m/Y|before:tomorrow|after:01/01/1900',
            'sem_cpf' => 'boolean',
            'cpf' => ['nullable', 'required_if:sem_cpf,false', 'prohibited_if:sem_cpf,true', new Cpf, Rule::unique('perfis', 'cpf')->ignore($perfilId)],
            'sem_rg' => 'boolean',
            'rg' => 'nullable|required_if:sem_rg,false|max:30',
            'passaporte' => 'nullable|max:30',
            'telefone' => 'required|digits_between:10,11',
            'celular' => 'nullable|digits_between:10,11',
            'emergencia_nome' => 'required|max:255',
            'emergencia_telefone' => 'required|digits_between:10,11',
            'cep' => 'required|digits:8',
            'estado' => ['required', Rule::in(array_keys(config('cepe.estados')))],
            'cidade' => 'required|max:255',
            'bairro' => 'required|max:255',
            'endereco' => 'required|max:255',
        ];

        foreach (['faixa_grau', 'associacao', 'formacao', 'instituicao', 'profissao', 'organizacao'] as $campo) {
            $rules[$campo] = 'nullable|max:255';
        }

        if ($alvo->local) {
            $rules['name'] = 'required|max:255';
            $rules['email'] = ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($alvo->id)];
            $rules['password'] = ['nullable', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()];
        }

        if (Gate::allows('admin')) {
            $rules['numero_cepeusp'] = 'nullable|digits_between:1,20';
            $rules['credito'] = 'nullable|integer|min:0';
            $rules['vinculo_usp'] = 'nullable|max:255';
            $rules['unidade_usp'] = 'nullable|max:255';
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'data_nascimento' => 'data de nascimento',
            'emergencia_nome' => 'contato de emergência',
            'emergencia_telefone' => 'telefone de emergência',
            'endereco' => 'endereço',
        ];
    }
}
