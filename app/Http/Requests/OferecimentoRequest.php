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
            'periodo_semestre' => 'required|in:01,02',
            'periodo_ano' => 'required|integer',
            'gratuito_ou_sem_pagamento' => 'nullable',
            'formas_pagamento' => 'nullable|array',
            'atestado_medico' => 'nullable',
            'exame_dermatologico' => 'nullable',
        ];

        $rules['periodos.*.ativo'] = 'nullable';
        $rules['periodos.*.inicio_data'] = 'nullable|date_format:d/m/Y';
        $rules['periodos.*.fim_data'] = 'nullable|date_format:d/m/Y';
        $rules['periodos.*.inicio_horario'] = 'nullable|date_format:H:i';
        $rules['periodos.*.fim_horario'] = 'nullable|date_format:H:i';

        return $rules;
    }
}