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
            $table->foreignId('carteira_id')->constrained()->cascadeOnDelete();
            $table->string('tipo');
            $table->string('arquivo');

            $table->unique(['carteira_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carteira_documentos');
    }
};
