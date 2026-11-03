<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presenca extends Model
{
    public const PRESENTE = 'presente';
    public const FALTA = 'falta';
    public const JUSTIFICADA = 'justificada';

    public const ALUNO = 'aluno';
    public const PROFESSOR = 'professor';
    public const GESTOR = 'gestor';

    protected $fillable = [
        'chamada_id',
        'matricula_id',
        'status',
        'origem',
    ];

    public function chamada()
    {
        return $this->belongsTo(Chamada::class);
    }

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    public function getStatusNomeAttribute(): string
    {
        return config('cepe.status_presenca.' . $this->status, $this->status);
    }
}
