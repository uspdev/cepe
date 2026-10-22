<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Atividade extends Model
{
    protected $fillable = [
        'nome',
        'descricao',
        'tipo',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function oferecimentos()
    {
        return $this->hasMany(Oferecimento::class);
    }
}