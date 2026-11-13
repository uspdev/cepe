<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cobrancas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained()->restrictOnDelete();
            $table->string('metodo')->default('boleto');
            $table->unsignedTinyInteger('parcela');
            $table->unsignedTinyInteger('total_parcelas');
            $table->unsignedInteger('valor_centavos');
            $table->date('vencimento');
            $table->timestamp('expira_em')->nullable();
            $table->string('nome_sacado');
            $table->string('cpf_cnpj', 14);
            $table->string('email_sacado');
            $table->string('codigo_boleto')->nullable()->unique();
            $table->string('id_pix')->nullable()->unique();
            $table->longText('pix_copia_cola')->nullable();
            $table->longText('pix_qr_code_img_base64')->nullable();
            $table->string('status')->default('emitindo');
            $table->unsignedTinyInteger('tentativas_emissao')->default(0);
            $table->string('erro_codigo')->nullable();
            $table->unsignedInteger('valor_pago_centavos')->nullable();
            $table->timestamp('emitido_em')->nullable();
            $table->timestamp('pago_em')->nullable();
            $table->timestamp('consultado_em')->nullable();
            $table->timestamp('cancelado_em')->nullable();
            $table->timestamps();

            $table->unique(['matricula_id', 'parcela']);
            $table->index(['status', 'vencimento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cobrancas');
    }
};
