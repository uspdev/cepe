<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParqResposta extends Model
{
    protected $table = 'parq_respostas';

    protected $fillable = ['atestado_id', 'respostas', 'termo_aceito', 'aceite_ip', 'aceito_em'];

    protected $casts = [
        'respostas' => 'array',
        'termo_aceito' => 'boolean',
        'aceito_em' => 'datetime',
    ];

    public function atestado()
    {
        return $this->belongsTo(Atestado::class);
    }

    public function temRespostaPositiva(): bool
    {
        return in_array('sim', $this->respostas ?? [], true);
    }
}
