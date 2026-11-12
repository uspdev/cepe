<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carteira extends Model
{
    public const STATUS_GERENCIAVEIS = [
        'aprovada', 'aguardando_pagamento', 'em_confeccao', 'disponivel', 'ativa', 'inativa',
    ];

    /** Status em que a carteira já dá direito ao benefício (pagamento confirmado). */
    public const STATUS_BENEFICIO = ['em_confeccao', 'disponivel', 'ativa'];

    /** Transições permitidas na análise da SRU, por status atual. */
    public const TRANSICOES = [
        'aprovada' => ['aguardando_pagamento', 'inativa'],
        'aguardando_pagamento' => ['em_confeccao', 'inativa'],
        'em_confeccao' => ['disponivel', 'inativa'],
        'disponivel' => ['ativa', 'inativa'],
        'ativa' => ['inativa'],
        'inativa' => [],
    ];

    protected $fillable = [
        'user_id', 'categoria', 'nome', 'cpf', 'data_nascimento', 'parentesco',
        'status', 'observacao', 'numero', 'via', 'validade_inicio', 'validade_fim',
        'analisado_por', 'analisado_em',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'validade_inicio' => 'date',
        'validade_fim' => 'date',
        'analisado_em' => 'datetime',
    ];

    public function solicitacoes()
    {
        return $this->hasMany(SolicitacaoCarteira::class, 'carteira_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function analisador()
    {
        return $this->belongsTo(User::class, 'analisado_por');
    }

    public function config(?string $chave = null): mixed
    {
        return config('cepe.carteiras.categorias.'.$this->categoria.($chave ? '.'.$chave : ''));
    }

    public function beneficiarioProprio(): bool
    {
        return $this->config('beneficiario') === 'proprio';
    }

    /** Com direito ao benefício: pagamento confirmado e dentro da validade. */
    public function vigente(): bool
    {
        return in_array($this->status, self::STATUS_BENEFICIO, true)
            && $this->validade_fim && ! $this->validade_fim->isBefore(today())
            && (! $this->validade_inicio || ! $this->validade_inicio->isAfter(today()));
    }

    public function scopeVigentes($query)
    {
        return $query->whereIn('status', self::STATUS_BENEFICIO)
            ->whereDate('validade_fim', '>=', now()->toDateString())
            ->where(fn ($q) => $q->whereNull('validade_inicio')->orWhereDate('validade_inicio', '<=', now()->toDateString()));
    }

    /** Próximos status que a SRU pode registrar a partir do atual. */
    public function proximosStatus(): array
    {
        return self::TRANSICOES[$this->status] ?? [];
    }

    public function getStatusEfetivoAttribute(): string
    {
        $vencida = in_array($this->status, array_merge(self::STATUS_BENEFICIO, ['aprovada', 'aguardando_pagamento']), true)
            && $this->validade_fim && $this->validade_fim->isBefore(today());

        return $vencida ? 'vencida' : $this->status;
    }

    public function getStatusNomeAttribute(): string
    {
        return config('cepe.carteiras.status.'.$this->status_efetivo, $this->status_efetivo);
    }

    public function getCategoriaNomeAttribute(): string
    {
        return $this->config('rotulo') ?? $this->categoria;
    }

    public function getParentescoNomeAttribute(): ?string
    {
        return $this->parentesco ? config('cepe.carteiras.parentescos.'.$this->parentesco, $this->parentesco) : null;
    }
}
