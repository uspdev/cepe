<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatriculaMetadado extends Model
{
    protected $table = 'matricula_metadados';

    protected $fillable = [
        'matricula_id',
        'chave',
        'valor',
    ];

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }
}
