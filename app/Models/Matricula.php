<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Turma;
use App\Models\MatriculaMetadado;

class Matricula extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function turma()
    {
        return $this->belongsTo(Turma::class);
    }
    
    public function metadados()
    {
        return $this->hasMany(MatriculaMetadado::class);
    }
}