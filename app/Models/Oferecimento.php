<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Oferecimento extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function atividade()
    {
        return $this->belongsTo(Atividade::class);
    }
}