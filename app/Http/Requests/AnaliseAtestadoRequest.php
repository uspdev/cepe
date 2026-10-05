<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnaliseAtestadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'status' => 'required|in:aprovado,reprovado',
            'observacao' => 'nullable|required_if:status,reprovado|string',
        ];
    }
}
