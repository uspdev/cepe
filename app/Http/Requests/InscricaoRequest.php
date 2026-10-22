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
            ? array_keys(app(InscricaoService::class)->perfisDisponiveis($turma, $this->user()))
            : [];

        return [
            'perfil' => ['required', Rule::in($perfis)],
            'image_use_authorization' => ['required', Rule::in(['autoriza', 'nao_autoriza'])],
            'regulamento' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return [
            'image_use_authorization' => 'autorização de uso de imagem',
            'regulamento' => 'regulamento',
        ];
    }

    public function messages(): array
    {
        return [
            'perfil.in' => 'Selecione um perfil de inscrição disponível.',
            'image_use_authorization.required' => 'Informe se autoriza o uso de imagem.',
            'regulamento.accepted' => 'É necessário aceitar o regulamento para concluir a inscrição.',
        ];
    }
}
