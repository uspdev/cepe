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
        Schema::table('turmas', function (Blueprint $table) {
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

            // Taxas do primeiro período de inscrição
            $table->decimal('taxa_usp', 8, 2)->nullable();
            $table->decimal('taxa_dependentes', 8, 2)->nullable();
            $table->decimal('taxa_externa', 8, 2)->nullable();
            $table->decimal('taxa_terceira_idade', 8, 2)->nullable();
            $table->decimal('taxa_cepe', 8, 2)->nullable();

            // Taxas do segundo período de inscrição
            $table->decimal('taxa_usp_2', 8, 2)->nullable();
            $table->decimal('taxa_dependentes_2', 8, 2)->nullable();
            $table->decimal('taxa_externa_2', 8, 2)->nullable();
            $table->decimal('taxa_terceira_idade_2', 8, 2)->nullable();
            $table->decimal('taxa_cepe_2', 8, 2)->nullable();

            $table->text('info_contato')->nullable();
            $table->text('declaracao')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('turmas', function (Blueprint $table) {
            $table->dropColumn([
                'sexo', 'dias_semana', 'horario_inicio', 'horario_fim',
                'professor', 'idade_minima', 'idade_maxima', 'nivel', 'local',
                'vagas_usp', 'vagas_papfe', 'vagas_externa', 'observacoes',
                'taxa_usp', 'taxa_dependentes', 'taxa_externa',
                'taxa_terceira_idade', 'taxa_cepe',
                'taxa_usp_2', 'taxa_dependentes_2', 'taxa_externa_2',
                'taxa_terceira_idade_2', 'taxa_cepe_2',
                'info_contato', 'declaracao',
            ]);
        });
    }
};
