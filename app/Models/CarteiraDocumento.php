<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarteiraDocumento extends Model
{
    protected $fillable = ['solicitacao_id', 'tipo', 'arquivo'];

    public function solicitacao()
    {
        return $this->belongsTo(SolicitacaoCarteira::class, 'solicitacao_id');
    }

    public function getTipoNomeAttribute(): string
    {
        return config('cepe.carteiras.documentos.'.$this->tipo, $this->tipo);
    }
}
