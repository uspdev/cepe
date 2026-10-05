<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atestados', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tipo'); // atestado_medico, exame_dermatologico, parq
            $table->string('arquivo')->nullable();
            $table->date('emitido_em');
            $table->date('valido_ate')->nullable();
            $table->string('status')->default('em_analise');
            $table->foreignId('analisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('analisado_em')->nullable();
            $table->text('observacao')->nullable();

            $table->index(['user_id', 'tipo']);
            $table->index('status');
        });

        Schema::create('parq_respostas', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('atestado_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('respostas');
            $table->boolean('termo_aceito')->default(false);
            $table->string('aceite_ip')->nullable();
            $table->dateTime('aceito_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parq_respostas');
        Schema::dropIfExists('atestados');
    }
};
