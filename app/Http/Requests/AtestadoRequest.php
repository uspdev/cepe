<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AtestadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        $parq = $this->input('tipo') === 'parq';
        $config = config('cepe.atestados');

        $rules = [
            'tipo' => ['required', Rule::in(array_keys($config['tipos']))],
        ];

        if (! $parq) {
            $rules['emitido_em'] = 'required|date_format:d/m/Y|before_or_equal:today';
        }

        if ($parq) {
            $rules['respostas'] = 'required|array';
            foreach (array_keys($config['parq']['perguntas']) as $n) {
                $rules["respostas.$n"] = 'required|in:sim,nao';
            }
            $rules['termo_aceito'] = [Rule::requiredIf(in_array('sim', (array) $this->input('respostas'), true)), 'nullable', 'accepted'];
        } else {
            $rules['arquivo'] = "required|file|mimes:{$config['arquivo']['mimes']}|max:{$config['arquivo']['max_kb']}";
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'respostas.*.required' => 'Responda todas as perguntas do PAR-Q.',
            'termo_aceito.required' => 'É necessário aceitar o termo de responsabilidade.',
            'termo_aceito.accepted' => 'É necessário aceitar o termo de responsabilidade.',
        ];
    }
}
