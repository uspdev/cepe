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
            'ano_nascimento_minimo' => 'nullable|integer|digits:4',
            'ano_nascimento_maximo' => 'nullable|integer|digits:4|gte:ano_nascimento_minimo',
            'vagas' => 'nullable|array',
            'vagas.*' => ['nullable', function ($attribute, $value, $fail) {
                foreach (is_array($value) ? $value : [$value] as $quantidade) {
                    if ($quantidade !== null && (!is_numeric($quantidade) || $quantidade < 0 || floor($quantidade) != $quantidade)) {
                        return $fail('As vagas devem ser números inteiros maiores ou iguais a zero.');
                    }
                }
            }],
            'observacoes' => 'nullable|string',
            'taxas' => 'nullable|array',
            'taxas.*.perfil' => 'required_with:taxas.*.valor|in:' . implode(',', array_keys(config('cepe.perfil'))),
            'taxas.*.periodo_inscricao' => 'required_with:taxas.*.valor|in:1,2',
            'taxas.*.valor' => 'nullable|numeric|min:0',
            'taxas.*' => 'array:perfil,periodo_inscricao,valor',
            'info_contato' => 'nullable|string',
            'declaracao' => 'nullable|string',
        ];
        return $rules;
    }
}
