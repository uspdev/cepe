<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TurmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** Taxa por vínculo exige a taxa geral do mesmo perfil e período. */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $preenchida = fn ($taxa) => isset($taxa['valor']) && $taxa['valor'] !== '';
            $taxas = array_filter((array) $this->input('taxas', []), 'is_array');

            $gerais = collect($taxas)
                ->filter(fn ($taxa) => $preenchida($taxa) && empty($taxa['vinculo']))
                ->map(fn ($taxa) => ($taxa['perfil'] ?? '') . '|' . ($taxa['periodo_inscricao'] ?? ''))
                ->all();

            foreach ($taxas as $chave => $taxa) {
                $semGeral = ! in_array(($taxa['perfil'] ?? '') . '|' . ($taxa['periodo_inscricao'] ?? ''), $gerais, true);
                if ($preenchida($taxa) && ! empty($taxa['vinculo']) && $semGeral) {
                    $validator->errors()->add("taxas.{$chave}.valor", 'Informe a taxa geral do perfil e do período antes da taxa por vínculo.');
                }
            }
        });
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
            'professor_id' => ['nullable', 'integer', function ($attribute, $value, $fail) {
                if ($value && ! \App\Models\User::professores()->whereKey($value)->exists()) {
                    $fail('Selecione um professor cadastrado.');
                }
            }],
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
            'taxas.*.valor' => 'nullable|integer|min:0',
            'taxas.*.vinculo' => 'nullable|in:' . implode(',', array_keys(config('cepe.taxa_vinculo.vinculos'))),
            'taxas.*' => 'array:perfil,periodo_inscricao,valor,vinculo',
            'info_contato' => 'nullable|string',
            'declaracao' => 'nullable|string',
        ];
        return $rules;
    }
}
