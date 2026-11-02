<?php

namespace App\Http\Requests;

use App\Models\Atividade;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AtividadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $atividade = $this->route('atividade');

        // autoriza antes de validar: quem não atua no grupo da atividade recebe 403, não erros de formulário
        return $this->user() !== null
            && ($atividade ? $this->user()->can('update', $atividade) : $this->user()->can('create', Atividade::class));
    }

    public function rules(){
        $rules = [
            'nome' => 'required',
            'descricao' => 'required',
            // o tipo precisa pertencer a um grupo em que o usuário atua (gestor-cursos não cria eventos)
            'tipo' => ['required', Rule::in(array_keys(config('cepe.tipos_inscricao'))), Rule::in($this->user()->tiposPermitidos())],
        ];
        return $rules;
    }
}