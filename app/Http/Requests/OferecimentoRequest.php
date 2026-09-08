<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class OferecimentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(){
        $rules = [
            'atividade_id' => 'required',
        ];
        return $rules;
    }
}