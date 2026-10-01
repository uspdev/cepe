<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Turma extends Model
{
    protected $fillable = [
        'oferecimento_id',
        'sexo',
        'dias_semana',
        'horario_inicio',
        'horario_fim',
        'professor',
        'idade_minima',
        'idade_maxima',
        'nivel',
        'local',
        'vagas_usp',
        'vagas_papfe',
        'vagas_externa',
        'observacoes',
        'info_contato',
        'declaracao',
    ];

    protected $casts = [
        'sexo' => 'array',
        'dias_semana' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function oferecimento()
    {
        return $this->belongsTo(Oferecimento::class);
    }

    public function matriculas()
    {
        return $this->hasMany(Matricula::class);
    }

    public function taxas()
    {
        return $this->hasMany(TaxaTurma::class);
    }
}