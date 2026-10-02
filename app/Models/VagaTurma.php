<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VagaTurma extends Model
{
    public const RESERVADA = 'reservada';

    protected $table = 'vagas_turma';

    protected $fillable = [
        'turma_id',
        'perfil',
        'tamanho',
        'quantidade',
    ];

    public function turma()
    {
        return $this->belongsTo(Turma::class);
    }

    /**
     * Estrutura de vagas aplicável a um tipo de atividade (cursos e eventos usam 'padrao').
     */
    public static function configuracao(?string $tipo): array
    {
        $config = config('cepe.vagas.' . $tipo) ?? config('cepe.vagas.padrao');
        $config['tamanhos'] = $config['tamanhos'] ? config('cepe.tamanhos_camiseta.' . $config['tamanhos']) : [];

        return $config;
    }
}
