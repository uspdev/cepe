<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Atividade extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function oferecimentos()
    {
        return $this->hasMany(Oferecimento::class);
    }
}