<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarteiraDocumento extends Model
{
    protected $fillable = ['carteira_id', 'tipo', 'arquivo'];

    public function carteira()
    {
        return $this->belongsTo(Carteira::class);
    }

    public function getTipoNomeAttribute(): string
    {
        return config('cepe.carteiras.documentos.'.$this->tipo, $this->tipo);
    }
}
