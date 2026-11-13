<?php

namespace App\Http\Requests;

use App\Services\CobrancaService;
use App\Services\InscricaoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InscricaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $turma = $this->route('turma');
        $inscricoes = app(InscricaoService::class);
        $opcoes = $turma ? $inscricoes->perfisComPagamentoDisponivel($turma, $this->user()) : [];
        $perfis = array_keys($opcoes);
        $perfil = $this->input('perfil');
        $requerPagamento = $turma
            && in_array($perfil, $perfis, true)
            && $inscricoes->requerPagamento($turma, $this->user(), $perfil);
        $metodos = $turma && in_array($perfil, $perfis, true)
            ? $inscricoes->metodosPagamentoDisponiveis($turma, $this->user(), $perfil)
            : [];
        $parcelamentoDisponivel = $turma
            && $this->input('metodo_pagamento') === 'boleto'
            && isset($opcoes[$perfil])
            && CobrancaService::parcelamentoDisponivel(
                $turma->oferecimento->atividade->tipo,
                'boleto',
                (int) $opcoes[$perfil]['valor'],
            );

        return [
            'perfil' => ['required', Rule::in($perfis)],
            'metodo_pagamento' => array_merge(
                $requerPagamento ? ['required'] : ['nullable'],
                [Rule::in($metodos)],
            ),
            'parcelas' => $parcelamentoDisponivel ? ['required', 'integer', 'in:1,2,3'] : ['prohibited'],
            'image_use_authorization' => ['required', Rule::in(['autoriza', 'nao_autoriza'])],
            'regulamento' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return [
            'image_use_authorization' => 'autorização de uso de imagem',
            'metodo_pagamento' => 'forma de pagamento',
            'regulamento' => 'regulamento',
        ];
    }

    public function messages(): array
    {
        return [
            'perfil.in' => 'Selecione um perfil de inscrição disponível.',
            'image_use_authorization.required' => 'Informe se autoriza o uso de imagem.',
            'metodo_pagamento.required' => 'Selecione uma forma de pagamento.',
            'metodo_pagamento.in' => 'Forma de pagamento indisponível para esta atividade.',
            'parcelas.required' => 'Escolha a quantidade de parcelas.',
            'parcelas.prohibited' => 'O parcelamento não está disponível para esta inscrição.',
            'regulamento.accepted' => 'É necessário aceitar o regulamento para concluir a inscrição.',
        ];
    }
}
