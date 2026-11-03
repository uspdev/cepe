<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chamada extends Model
{
    protected $fillable = [
        'turma_id',
        'data',
        'user_id',
    ];

    protected $casts = [
        'data' => 'date',
    ];

    public function turma()
    {
        return $this->belongsTo(Turma::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function presencas()
    {
        return $this->hasMany(Presenca::class);
    }
}
