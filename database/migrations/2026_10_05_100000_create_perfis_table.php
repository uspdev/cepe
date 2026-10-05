<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfis', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // identificação
            $table->string('sexo', 1)->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('cpf', 11)->nullable()->unique();
            $table->boolean('sem_cpf')->default(false);
            $table->string('rg')->nullable();
            $table->boolean('sem_rg')->default(false);
            $table->string('passaporte')->nullable();

            // contato e emergência
            $table->string('telefone', 11)->nullable();
            $table->string('celular', 11)->nullable();
            $table->string('emergencia_nome')->nullable();
            $table->string('emergencia_telefone', 11)->nullable();

            // endereço
            $table->string('cep', 8)->nullable();
            $table->string('estado', 2)->nullable();
            $table->string('cidade')->nullable();
            $table->string('bairro')->nullable();
            $table->string('endereco')->nullable();

            // esportivo e profissional
            $table->string('faixa_grau')->nullable();
            $table->string('associacao')->nullable();
            $table->string('formacao')->nullable();
            $table->string('instituicao')->nullable();
            $table->string('profissao')->nullable();
            $table->string('organizacao')->nullable();

            // restritos (somente admin)
            $table->string('numero_cepeusp')->nullable();
            $table->unsignedInteger('credito')->default(0); // reais inteiros
            $table->string('vinculo_usp')->nullable();
            $table->string('unidade_usp')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfis');
    }
};
