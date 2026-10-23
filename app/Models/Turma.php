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

    public function filas()
    {
        return $this->hasMany(Fila::class);
    }

    public function configuracaoVagas(): array
    {
        return VagaTurma::configuracao($this->oferecimento?->atividade?->tipo);
    }

    public function matriculasAtivas()
    {
        return $this->matriculas()->ativas();
    }

    public function vagasDoPerfil(string $perfil): int
    {
        return (int) $this->vagas->where('perfil', $perfil)->sum('quantidade');
    }

    public function taxaDoPerfil(string $perfil, int $periodoInscricao): ?TaxaTurma
    {
        return $this->taxas
            ->where('perfil', $perfil)
            ->where('periodo_inscricao', $periodoInscricao)
            ->first();
    }

    public function getDiasSemanaFormatadosAttribute(): string
    {
        $mapa = [
            'segunda' => '2ª', 'terca' => '3ª', 'quarta' => '4ª',
            'quinta' => '5ª', 'sexta' => '6ª', 'sabado' => 'Sáb', 'domingo' => 'Dom',
        ];

        return implode('/', array_map(fn ($d) => $mapa[$d] ?? ucfirst($d), $this->dias_semana ?? []));
    }

    public function getHorarioFormatadoAttribute(): string
    {
        if (! $this->horario_inicio && ! $this->horario_fim) {
            return '-';
        }

        return substr((string) $this->horario_inicio, 0, 5) . ' - ' . substr((string) $this->horario_fim, 0, 5);
    }

    public function taxas()
    {
        return $this->hasMany(TaxaTurma::class);
    }
}