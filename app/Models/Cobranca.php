<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cobranca extends Model
{
    public const EMITINDO = 'emitindo';

    public const EMITIDA = 'emitida';

    public const PAGA = 'paga';

    public const VENCIDA = 'vencida';

    public const CANCELAMENTO_PENDENTE = 'cancelamento_pendente';

    public const CANCELADA = 'cancelada';

    public const FALHA_EMISSAO = 'falha_emissao';

    public const EMISSAO_INDETERMINADA = 'emissao_indeterminada';

    public const BOLETO = 'boleto';

    public const PIX = 'pix';

    protected $fillable = [
        'matricula_id',
        'metodo',
        'parcela',
        'total_parcelas',
        'valor_centavos',
        'vencimento',
        'expira_em',
        'nome_sacado',
        'cpf_cnpj',
        'email_sacado',
        'codigo_boleto',
        'id_pix',
        'pix_copia_cola',
        'pix_qr_code_img_base64',
        'status',
        'tentativas_emissao',
        'erro_codigo',
        'valor_pago_centavos',
        'emitido_em',
        'pago_em',
        'consultado_em',
        'cancelado_em',
    ];

    protected function casts(): array
    {
        return [
            'vencimento' => 'date',
            'expira_em' => 'datetime',
            'emitido_em' => 'datetime',
            'pago_em' => 'datetime',
            'consultado_em' => 'datetime',
            'cancelado_em' => 'datetime',
            'valor_pago_centavos' => 'integer',
        ];
    }

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    public function valorReais(): float
    {
        return $this->valor_centavos / 100;
    }

    public function getStatusNomeAttribute(): string
    {
        return config('cepe.pagamentos.status.'.$this->status, $this->status);
    }

    public function getMetodoNomeAttribute(): string
    {
        return $this->metodo === self::PIX ? 'Pix' : 'Boleto';
    }
}
