<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxas_carteiras', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('categoria')->unique();
            $table->integer('valor')->nullable();
            $table->integer('valor_dependente')->nullable();
            $table->string('observacao')->nullable();
            $table->foreignId('atualizado_por')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxas_carteiras');
    }
};
