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

    protected $fillable = ['user_id', ...self::CAMPOS_USUARIO, ...self::CAMPOS_ADMIN];

    protected $casts = [
        'data_nascimento' => 'date',
        'sem_cpf' => 'boolean',
        'sem_rg' => 'boolean',
        'credito' => 'integer',
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
