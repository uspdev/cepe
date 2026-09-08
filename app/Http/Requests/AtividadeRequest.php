<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
        ];
        return $rules;
    }
}