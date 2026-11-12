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
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status');
            $table->string('numero')->unique();
            $table->unsignedSmallInteger('via')->default(1);
            $table->string('categoria');
            $table->string('nome')->nullable();
            $table->string('cpf', 11)->nullable()->index();
            $table->date('data_nascimento')->nullable();
            $table->string('parentesco')->nullable();
            $table->text('observacao')->nullable();
            $table->foreignId('analisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('analisado_em')->nullable();
            $table->date('validade_inicio')->nullable();
            $table->date('validade_fim')->nullable();
        });

        Schema::create('solicitacoes_carteiras', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('solicitante_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('categoria');

            // beneficiário
            $table->string('nome');
            $table->string('cpf', 11)->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('parentesco')->nullable();

            $table->string('status')->default('em_analise');
            $table->text('observacao')->nullable();
            $table->foreignId('analisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('analisado_em')->nullable();

            $table->foreignId('carteira_id')->nullable()->constrained('carteiras')->nullOnDelete();

            $table->index('status');
            $table->index('cpf');
            $table->index('carteira_id');
        });
    }

};
