<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Oferecimento extends Model
{
    protected $fillable = [
        'atividade_id',
        'gratuito_ou_sem_pagamento',
        'sem_inscricoes',
        'meio_pagamento', // <-- Novo campo
        'periodo_semestre',
        'periodo_ano',
        'curso_regular',
        'atestado_medico',
        'exame_dermatologico',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function atividade()
    {
        return $this->belongsTo(Atividade::class);
    }

    public function turmas()
    {
        return $this->hasMany(Turma::class);
    }

    public function periodos()
    {
        return $this->hasMany(PeriodoOferecimento::class);
    }
}