<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fila extends Model
{
    protected $table = 'filas';

    protected $fillable = [
        'user_id',
        'vagas_turma_id',
        'turma_id',
        'posicao',
        'status',
        'head',
    ];

    protected $casts = [
        'posicao' => 'integer',
        'status' => 'boolean',
        'head' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function turma()
    {
        return $this->belongsTo(Turma::class);
    }

    public function vagaTurma()
    {
        return $this->belongsTo(VagaTurma::class, 'vagas_turma_id');
    }
}

