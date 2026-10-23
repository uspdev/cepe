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
        Schema::create('turmas', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('oferecimento_id')->nullable();
            $table->foreign('oferecimento_id')->references('id')->on('oferecimentos')->nullOnDelete();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->json('sexo')->nullable();
            $table->json('dias_semana')->nullable();
            $table->time('horario_inicio')->nullable();
            $table->time('horario_fim')->nullable();
            $table->string('professor')->nullable();
            $table->integer('idade_minima')->nullable();
            $table->integer('idade_maxima')->nullable();
            $table->string('nivel')->nullable();
            $table->string('local')->nullable();
            $table->integer('vagas_usp')->nullable();
            $table->integer('vagas_papfe')->nullable();
            $table->integer('vagas_externa')->nullable();
            $table->text('observacoes')->nullable();

            $table->text('info_contato')->nullable();
            $table->text('declaracao')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turmas');
    }
};