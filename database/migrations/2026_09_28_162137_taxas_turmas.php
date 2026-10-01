<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('taxas_turma', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turma_id')->constrained('turmas')->onDelete('cascade');
            $table->string('perfil'); // Ex: tax_in, tax_ex, tax_papfe, tax_alumni, tax_cepe
            $table->integer('valor');
            $table->integer('periodo_inscricao'); // 1º ou 2º período de inscrição
            $table->timestamps();

            $table->unique(['turma_id', 'perfil', 'periodo_inscricao']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxas_turma');
    }
};
