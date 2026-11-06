<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarteiraTaxa extends Model
{
    protected $table = 'taxas_carteiras';

    protected $fillable = ['categoria', 'valor', 'valor_dependente', 'observacao', 'atualizado_por'];

    protected $casts = [
        'valor' => 'integer',
        'valor_dependente' => 'integer',
    ];

    public function atualizador()
    {
        return $this->belongsTo(User::class, 'atualizado_por');
    }

    public static function moeda(float|string $valor): string
    {
        return 'R$ '.number_format((float) $valor, 2, ',', '.');
    }

    public static function descricao(string $categoria): string
    {
        $taxa = static::firstWhere('categoria', $categoria);
        if (! $taxa || $taxa->valor === null) {
            return (string) config("cepe.carteiras.categorias.{$categoria}.taxa", '');
        }

        return collect([
            'Taxa anual '.self::moeda($taxa->valor),
            $taxa->valor_dependente !== null ? '(dependente '.self::moeda($taxa->valor_dependente).')' : null,
            $taxa->observacao,
        ])->filter()->implode(' ');
    }
}
