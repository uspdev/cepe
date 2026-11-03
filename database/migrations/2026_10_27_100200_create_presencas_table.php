<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presencas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chamada_id');
            $table->foreign('chamada_id')->references('id')->on('chamadas')->cascadeOnDelete();
            $table->unsignedBigInteger('matricula_id');
            $table->foreign('matricula_id')->references('id')->on('matriculas')->cascadeOnDelete();
            $table->string('status');
            $table->string('origem');
            $table->timestamps();
            $table->unique(['chamada_id', 'matricula_id']);
        });
    }
};
