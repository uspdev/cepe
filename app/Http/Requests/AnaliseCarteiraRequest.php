<?php

namespace App\Http\Requests;

use App\Models\Carteira;
use App\Models\SolicitacaoCarteira;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class AnaliseCarteiraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('analisar', $this->route('solicitacao'));
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:aprovada,pendente,reprovada',
            'numero' => 'nullable|required_if:status,aprovada|digits_between:1,20',
            'via' => 'nullable|integer|min:1',
            'observacao' => 'nullable|required_if:status,pendente|required_if:status,reprovada|string',
        ];
    }

    /** Transição válida, número com o prefixo da categoria e sem uso por outra carteira. */
    public function after(): array
    {
        return [function ($validator) {
            /** @var SolicitacaoCarteira $solicitacao */
            $solicitacao = $this->route('solicitacao');
            $status = $this->input('status');

            if (! in_array($status, $solicitacao->proximosStatus(), true)) {
                $validator->errors()->add('status', 'Transição não permitida a partir do status atual.');

                return;
            }

            $numero = (string) $this->input('numero');
            if ($status === 'aprovada') {
                $prefixos = $solicitacao->config('prefixos') ?? [];
                if ($prefixos && ! Str::startsWith($numero, $prefixos)) {
                    $validator->errors()->add('numero', 'O número desta categoria deve começar com '.implode(' ou ', $prefixos).'.');
                }
            }

            if ($numero && Carteira::where('numero', $numero)
                ->when($solicitacao->carteira_id, fn ($q) => $q->where('id', '<>', $solicitacao->carteira_id))
                ->exists()) {
                $validator->errors()->add('numero', 'Este número já pertence a outra carteira.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'numero.required_if' => 'Informe o número da carteira para aprovar.',
            'observacao.required_if' => 'Descreva o motivo (documentação pendente ou reprovação).',
        ];
    }
}
