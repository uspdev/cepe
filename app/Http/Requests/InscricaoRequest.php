<?php

namespace App\Http\Requests;

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
        $perfis = $turma
            ? array_keys(app(InscricaoService::class)->perfisComPagamentoDisponivel($turma, $this->user()))
            : [];
        $perfil = $this->input('perfil');
        $requerPagamento = $turma
            && in_array($perfil, $perfis, true)
            && app(InscricaoService::class)->requerPagamento($turma, $this->user(), $perfil);
        $metodos = $turma && in_array($perfil, $perfis, true)
            ? app(InscricaoService::class)->metodosPagamentoDisponiveis($turma, $this->user(), $perfil)
            : [];

        return [
            'perfil' => ['required', Rule::in($perfis)],
            'metodo_pagamento' => array_merge(
                $requerPagamento ? ['required'] : ['nullable'],
                [Rule::in($metodos)],
            ),
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
            'regulamento.accepted' => 'É necessário aceitar o regulamento para concluir a inscrição.',
        ];
    }
}
