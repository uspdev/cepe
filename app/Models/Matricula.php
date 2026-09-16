<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Matricula extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function periodo()
    {
        return $this->belongsTo(Periodo::class);
    }

    public function turma()
    {
        return $this->belongsTo(Turma::class);
    }
}