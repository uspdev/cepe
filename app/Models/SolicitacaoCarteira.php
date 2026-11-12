<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitacaoCarteira extends Model
{
    protected $table = 'solicitacoes_carteiras';

    protected $fillable = [
        'solicitante_id', 'categoria', 'nome', 'cpf', 'data_nascimento', 'parentesco',
        'status', 'observacao', 'analisado_por', 'analisado_em', 'carteira_id',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'analisado_em' => 'datetime',
    ];

    public const TRANSICOES = [
        'em_analise' => ['aprovada', 'pendente', 'reprovada'],
        'pendente' => ['aprovada', 'reprovada'],
        'aprovada' => [],
        'reprovada' => [],
    ];

    public function solicitante()
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    public function carteira()
    {
        return $this->belongsTo(Carteira::class);
    }

    public function documentos()
    {
        return $this->hasMany(CarteiraDocumento::class, 'solicitacao_id');
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

    public function proximosStatus(): array
    {
        return self::TRANSICOES[$this->status] ?? [];
    }

    public function getStatusEfetivoAttribute(): string
    {
        return $this->status;
    }

    public function getStatusNomeAttribute(): string
    {
        return config('cepe.carteiras.status.'.$this->status, $this->status);
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
