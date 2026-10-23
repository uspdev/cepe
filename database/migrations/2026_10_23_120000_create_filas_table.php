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
        Schema::create('filas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('vagas_turma_id')->nullable()->constrained('vagas_turma')->onDelete('cascade');
            $table->foreignId('turma_id')->nullable()->constrained('turmas')->onDelete('cascade');
            $table->integer('posicao');
            $table->boolean('status')->default(true);
            $table->boolean('head')->default(false);
            $table->timestamps();

            $table->index(['turma_id', 'status']);
            $table->index(['vagas_turma_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filas');
    }
};

