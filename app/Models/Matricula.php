<?php

namespace App\Models;

use App\Models\User;
use App\Models\Turma;
use App\Models\MatriculaMetadado;
use Illuminate\Database\Eloquent\Model;

class Matricula extends Model
{
    public const MATRICULADA = 'matriculada';
    public const CANCELADA = 'cancelada';

    protected $fillable = [
        'user_id',
        'perfil',
        'turma_id',
        'periodo_inscricao',
        'periodo_oferecimento',
        'status',
    ];

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

    /** Matrículas que ocupam vaga: qualquer status exceto cancelada. */
    public function scopeAtivas($query)
    {
        return $query->where('status', '<>', self::CANCELADA);
    }

    public function getStatusNomeAttribute(): string
    {
        return config('cepe.status_matricula.' . $this->status, $this->status);
    }

    public function getPerfilNomeAttribute(): string
    {
        return config('cepe.perfil.' . $this->perfil, $this->perfil);
    }

    public function metadado(string $chave): ?string
    {
        return $this->metadados->firstWhere('chave', $chave)?->valor;
    }
}
