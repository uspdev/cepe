<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxaTurma extends Model
{
    protected $table = 'taxas_turma';

    protected $fillable = [
        'turma_id',
        'perfil',
        'valor',
        'periodo_inscricao',
    ];

    public function turma()
    {
        return $this->belongsTo(Turma::class);
    }
}
