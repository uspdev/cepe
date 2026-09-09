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
        Schema::create('periodos_oferecimento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oferecimento_id')->constrained('oferecimentos')->onDelete('cascade');
            $table->string('perfil'); // Ex: 'docente', 'discente', 'comunidade'
            $table->dateTime('inicio');
            $table->dateTime('fim');
            $table->timestamps();

            // Evita duplicar o mesmo perfil no mesmo oferecimento
            $table->unique(['oferecimento_id', 'perfil']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodos_oferecimento');
    }
};
