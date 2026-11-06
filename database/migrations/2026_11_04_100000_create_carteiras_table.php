<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carteiras', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('solicitante_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('categoria');

            // beneficiário
            $table->string('nome');
            $table->string('cpf', 11)->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('parentesco')->nullable();

            $table->string('status')->default('em_analise');
            $table->text('observacao')->nullable();
            $table->string('numero')->nullable();
            $table->unsignedSmallInteger('via')->nullable();
            $table->date('validade_inicio')->nullable();
            $table->date('validade_fim')->nullable();
            $table->foreignId('analisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('analisado_em')->nullable();

            $table->index('status');
            $table->index('cpf');
            $table->index('numero');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carteiras');
    }
};
