<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OferecimentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules()
    {
        $rules = [
            'atividade_id' => 'required|exists:atividades,id',
            'edicao' => 'required|integer|min:1|max:99',
            'ano' => 'required|integer|min:2000|max:2100',
            'formas_pagamento' => 'nullable|array',
            'formas_pagamento.*' => [Rule::in(['pix', 'boleto', 'gratuito'])],
            'atestado_medico' => 'nullable',
            'exame_dermatologico' => 'nullable',
            'limitar_matricula' => 'nullable',
        ];

        $rules['periodos.*.ativo'] = 'nullable';
        $rules['periodos.*.inicio_data'] = 'nullable|date_format:d/m/Y';
        $rules['periodos.*.fim_data'] = 'nullable|date_format:d/m/Y';
        $rules['periodos.*.inicio_horario'] = 'nullable|date_format:H:i';
        $rules['periodos.*.fim_horario'] = 'nullable|date_format:H:i';

        return $rules;
    }
}
