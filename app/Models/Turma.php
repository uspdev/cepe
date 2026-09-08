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
        'taxa_usp',
        'taxa_dependentes',
        'taxa_externa',
        'taxa_terceira_idade',
        'taxa_cepe',
        'taxa_usp_2',
        'taxa_dependentes_2',
        'taxa_externa_2',
        'taxa_terceira_idade_2',
        'taxa_cepe_2',
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
}