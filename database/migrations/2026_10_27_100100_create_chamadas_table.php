<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chamadas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('turma_id');
            $table->foreign('turma_id')->references('id')->on('turmas')->cascadeOnDelete();
            $table->date('data');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['turma_id', 'data']);
        });
    }
};
