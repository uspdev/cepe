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
        'ano_nascimento_minimo',
        'ano_nascimento_maximo',
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

    public function vagas()
    {
        return $this->hasMany(VagaTurma::class);
    }

    public function configuracaoVagas(): array
    {
        return VagaTurma::configuracao($this->oferecimento?->atividade?->tipo);
    }

    public function taxas()
    {
        return $this->hasMany(TaxaTurma::class);
    }
}