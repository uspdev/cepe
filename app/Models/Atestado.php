<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Atestado extends Model
{
    protected $fillable = ['user_id', 'tipo', 'arquivo', 'emitido_em', 'valido_ate', 'status', 'observacao'];

    protected $casts = [
        'emitido_em' => 'date',
        'valido_ate' => 'date',
        'analisado_em' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function analisador()
    {
        return $this->belongsTo(User::class, 'analisado_por');
    }

    public function parq()
    {
        return $this->hasOne(ParqResposta::class);
    }

    public function scopeAprovados($query)
    {
        return $query->where('status', 'aprovado');
    }

    public function scopePendentes($query)
    {
        return $query->where('status', 'em_analise');
    }

    public function getTipoNomeAttribute(): string
    {
        return config('cepe.atestados.tipos.'.$this->tipo, $this->tipo);
    }

    /** Status efetivo: aprovado com validade expirada conta como vencido. */
    public function getStatusEfetivoAttribute(): string
    {
        if ($this->status === 'aprovado' && $this->valido_ate && $this->valido_ate->isPast() && ! $this->valido_ate->isToday()) {
            return 'vencido';
        }

        return $this->status;
    }

    public function getStatusNomeAttribute(): string
    {
        return config('cepe.atestados.status.'.$this->status_efetivo, $this->status_efetivo);
    }

    public function validadeMeses(): int
    {
        return (int) config('cepe.atestados.validade_meses.'.$this->tipo, 12);
    }

    public static function valido(User $user, string $tipo): bool
    {
        return static::where('user_id', $user->id)
            ->where('tipo', $tipo)
            ->aprovados()
            ->whereDate('valido_ate', '>=', now()->toDateString())
            ->exists();
    }
}
