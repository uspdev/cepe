<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AtividadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(){
        $rules = [
            'nome' => 'required',
            'descricao' => 'required',
            'tipo' => ['required', Rule::in(array_keys(config('cepe.tipos_inscricao')))],
        ];
        return $rules;
    }
}