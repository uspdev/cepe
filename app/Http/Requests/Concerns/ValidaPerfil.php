<?php

namespace App\Http\Requests\Concerns;

use App\Models\Perfil;
use App\Rules\Cpf;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

/** Normalização, regras e dados dos campos do perfil, compartilhados entre `/perfil` e o cadastro de conta local. */
trait ValidaPerfil
{
    protected function normalizarPerfil(): void
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

    protected function regrasPerfil(?int $perfilId = null): array
    {
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

        return $rules;
    }

    protected function atributosPerfil(): array
    {
        return [
            'data_nascimento' => 'data de nascimento',
            'emergencia_nome' => 'contato de emergência',
            'emergencia_telefone' => 'telefone de emergência',
            'endereco' => 'endereço',
        ];
    }

    /** Campos do perfil editáveis pelo usuário, prontos para gravar. */
    public function dadosPerfil(): array
    {
        $dados = $this->safe()->only(Perfil::CAMPOS_USUARIO);
        $dados['data_nascimento'] = Carbon::createFromFormat('d/m/Y', $this->validated('data_nascimento'))->toDateString();

        if ($dados['sem_cpf']) {
            $dados['cpf'] = null;
        }
        if ($dados['sem_rg']) {
            $dados['rg'] = null;
        }

        return $dados;
    }
}
