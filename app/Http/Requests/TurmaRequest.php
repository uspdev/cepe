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
            'oferecimento_id' => 'required|exists:oferecimentos,id',
            'sexo' => 'nullable|array',
            'sexo.*' => 'in:m,f',
            'dias_semana' => 'nullable|array',
            'dias_semana.*' => 'in:segunda,terca,quarta,quinta,sexta,sabado,domingo',
            'horario_inicio' => 'nullable|date_format:H:i',
            'horario_fim' => 'nullable|date_format:H:i',
            'professor' => 'nullable|string|max:255',
            'idade_minima' => 'nullable|integer|min:1',
            'idade_maxima' => 'nullable|integer|min:1|gte:idade_minima',
            'nivel' => 'nullable|string|max:255',
            'local' => 'nullable|string|max:255',
            'vagas_usp' => 'nullable|integer|min:0',
            'vagas_papfe' => 'nullable|integer|min:0',
            'vagas_externa' => 'nullable|integer|min:0',
            'observacoes' => 'nullable|string',
            'taxa_usp' => 'nullable|numeric|min:0',
            'taxa_dependentes' => 'nullable|numeric|min:0',
            'taxa_externa' => 'nullable|numeric|min:0',
            'taxa_terceira_idade' => 'nullable|numeric|min:0',
            'taxa_cepe' => 'nullable|numeric|min:0',
            'taxa_usp_2' => 'nullable|numeric|min:0',
            'taxa_dependentes_2' => 'nullable|numeric|min:0',
            'taxa_externa_2' => 'nullable|numeric|min:0',
            'taxa_terceira_idade_2' => 'nullable|numeric|min:0',
            'taxa_cepe_2' => 'nullable|numeric|min:0',
            'info_contato' => 'nullable|string',
            'declaracao' => 'nullable|string',
        ];
        return $rules;
    }
}
