<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TurmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(){
        $rules = [
            'oferecimento_id' => 'required',
        ];
        return $rules;
    }
}