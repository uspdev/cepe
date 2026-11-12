<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carteira_documentos', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('solicitacao_id')->constrained('solicitacoes_carteiras');
            $table->string('tipo');
            $table->string('arquivo');

            $table->unique(['solicitacao_id', 'tipo']);
        });
    }
};
