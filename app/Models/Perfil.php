<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Perfil extends Model
{
    protected $table = 'perfis';

    public const CAMPOS_USUARIO = [
        'sexo', 'data_nascimento', 'cpf', 'sem_cpf', 'rg', 'sem_rg', 'passaporte',
        'telefone', 'celular', 'emergencia_nome', 'emergencia_telefone',
        'cep', 'estado', 'cidade', 'bairro', 'endereco',
        'faixa_grau', 'associacao', 'formacao', 'instituicao', 'profissao', 'organizacao',
    ];

    public const CAMPOS_ADMIN = ['numero_cepeusp', 'credito', 'vinculo_usp', 'unidade_usp'];

    /** Preenchidos pelo sistema (Senha Única / admin), não pelo usuário. */
    public const CAMPOS_SISTEMA = ['vinculos_usp', 'vinculo_manual'];

    protected $fillable = ['user_id', ...self::CAMPOS_USUARIO, ...self::CAMPOS_ADMIN, ...self::CAMPOS_SISTEMA];

    protected $casts = [
        'data_nascimento' => 'date',
        'sem_cpf' => 'boolean',
        'sem_rg' => 'boolean',
        'credito' => 'integer',
        'vinculos_usp' => 'array',
        'vinculo_manual' => 'boolean',
    ];

    /** Rótulos dos campos obrigatórios, na ordem de exibição. */
    public const OBRIGATORIOS = [
        'sexo' => 'Sexo',
        'data_nascimento' => 'Data de nascimento',
        'cpf' => 'CPF',
        'rg' => 'RG',
        'telefone' => 'Telefone',
        'emergencia_nome' => 'Contato de emergência',
        'emergencia_telefone' => 'Telefone de emergência',
        'cep' => 'CEP',
        'estado' => 'Estado',
        'cidade' => 'Cidade',
        'bairro' => 'Bairro',
        'endereco' => 'Endereço',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getIdadeAttribute(): ?int
    {
        return $this->data_nascimento?->age;
    }

    public function temVinculo(string $codigo): bool
    {
        return in_array(mb_strtoupper($codigo), $this->vinculos_usp ?? [], true);
    }

    /** Vínculo USP real (lista de `cepe.vinculos_usp_permitidos`). Perfis ainda não sincronizados caem no rótulo legado. */
    public function temVinculoUsp(): bool
    {
        if ($this->vinculo_manual) {
            return filled($this->vinculo_usp);
        }

        if ($this->vinculos_usp === null) {
            return filled($this->vinculo_usp) && $this->vinculo_usp !== 'Alumni';
        }

        return array_intersect($this->vinculos_usp, config('cepe.vinculos_usp_permitidos')) !== [];
    }

    /** 'dependente' (9063/9263), 'conveniado' (9163) ou null, pelo prefixo do número CEPEUSP. */
    public function categoriaCepeusp(): ?string
    {
        return match (substr((string) $this->numero_cepeusp, 0, 4)) {
            '9063', '9263' => 'dependente',
            '9163' => 'conveniado',
            default => null,
        };
    }

    /** true/false conforme a idade esteja na faixa do PAR-Q; null quando a data de nascimento é desconhecida. */
    public function parqPermitido(): ?bool
    {
        $idade = $this->idade;

        return $idade === null ? null : $idade >= config('cepe.atestados.parq.idade_min') && $idade <= config('cepe.atestados.parq.idade_max');
    }

    public function camposFaltantes(): array
    {
        $faltam = [];
        foreach (self::OBRIGATORIOS as $campo => $rotulo) {
            $dispensado = ($campo === 'cpf' && $this->sem_cpf) || ($campo === 'rg' && $this->sem_rg);
            if (! $dispensado && blank($this->{$campo})) {
                $faltam[] = $rotulo;
            }
        }

        return $faltam;
    }

    public function completo(): bool
    {
        return $this->camposFaltantes() === [];
    }
}
