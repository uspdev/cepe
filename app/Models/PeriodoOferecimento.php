<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodoOferecimento extends Model
{
    protected $table = 'periodos_oferecimento';

    protected $fillable = [
        'oferecimento_id',
        'perfil',
        'inicio',
        'fim',
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'fim' => 'datetime',
    ];

    public function oferecimento()
    {
        return $this->belongsTo(Oferecimento::class);
    }
}