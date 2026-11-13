<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Matricula extends Model
{
    public const MATRICULADA = 'matriculada';

    public const AGUARDANDO_PAGAMENTO = 'aguardando_pagamento';

    public const CANCELADA = 'cancelada';

    protected $fillable = [
        'user_id',
        'perfil',
        'turma_id',
        'periodo_inscricao',
        'periodo_oferecimento',
        'status',
        'valor_base',
        'desconto',
        'valor',
        'credito_usado',
        'revisao_credito_sru',
        'revisao_credito_sru_em',
    ];

    protected function casts(): array
    {
        return [
            'revisao_credito_sru' => 'boolean',
            'revisao_credito_sru_em' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function turma()
    {
        return $this->belongsTo(Turma::class);
    }

    public function metadados()
    {
        return $this->hasMany(MatriculaMetadado::class);
    }

    public function presencas()
    {
        return $this->hasMany(Presenca::class);
    }

    public function cobrancas()
    {
        return $this->hasMany(Cobranca::class)->orderBy('parcela');
    }

    /** Matrículas que ocupam vaga: qualquer status exceto cancelada. */
    public function scopeAtivas($query)
    {
        return $query->where('status', '<>', self::CANCELADA);
    }

    public function getStatusNomeAttribute(): string
    {
        return config('cepe.status_matricula.'.$this->status, $this->status);
    }

    public function getPerfilNomeAttribute(): string
    {
        return config('cepe.perfil.'.$this->perfil, $this->perfil);
    }

    public function metadado(string $chave): ?string
    {
        return $this->metadados->firstWhere('chave', $chave)?->valor;
    }
}
