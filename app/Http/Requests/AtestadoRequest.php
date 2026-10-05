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
            $rules['termo_aceito'] = in_array('sim', (array) $this->input('respostas'), true) ? ['required', 'accepted'] : ['nullable'];
        } else {
            $rules['arquivo'] = "required|file|mimes:{$config['arquivo']['mimes']}|max:{$config['arquivo']['max_kb']}";
        }

        return $rules;
    }

    public function after(): array
    {
        return [function ($validator) {
            if ($this->input('tipo') !== 'parq') {
                return;
            }
            $faixa = config('cepe.atestados.parq');
            $idade = $this->user()->perfil?->idade;

            if ($idade === null) {
                $validator->errors()->add('tipo', 'Informe sua data de nascimento no perfil para preencher o PAR-Q.');
            } elseif ($idade < $faixa['idade_min'] || $idade > $faixa['idade_max']) {
                $validator->errors()->add('tipo', "O PAR-Q é destinado a pessoas de {$faixa['idade_min']} a {$faixa['idade_max']} anos. Envie um atestado médico.");
            }
        }];
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
