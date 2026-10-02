<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $legado = [
        'vagas_usp' => 'tax_in',
        'vagas_papfe' => 'tax_papfe',
        'vagas_externa' => 'tax_ex',
    ];

    public function up(): void
    {
        Schema::create('vagas_turma', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turma_id')->constrained('turmas')->onDelete('cascade');
            $table->string('perfil'); // Ex: tax_in, tax_ex, tax_papfe, tax_alumni ou 'reservada'
            $table->string('tamanho')->default(''); // tamanho de camiseta (volta USP e voltinha); vazio quando não se aplica
            $table->unsignedInteger('quantidade');
            $table->timestamps();

            $table->unique(['turma_id', 'perfil', 'tamanho']);
        });

        foreach (DB::table('turmas')->get() as $turma) {
            foreach ($this->legado as $coluna => $perfil) {
                if ($turma->{$coluna} === null) {
                    continue;
                }
                DB::table('vagas_turma')->insert([
                    'turma_id' => $turma->id,
                    'perfil' => $perfil,
                    'tamanho' => '',
                    'quantidade' => $turma->{$coluna},
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('turmas', function (Blueprint $table) {
            $table->dropColumn(array_keys($this->legado));
            $table->unsignedSmallInteger('ano_nascimento_minimo')->nullable();
            $table->unsignedSmallInteger('ano_nascimento_maximo')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('turmas', function (Blueprint $table) {
            $table->dropColumn(['ano_nascimento_minimo', 'ano_nascimento_maximo']);
            foreach (array_keys($this->legado) as $coluna) {
                $table->integer($coluna)->nullable();
            }
        });

        foreach ($this->legado as $coluna => $perfil) {
            $vagas = DB::table('vagas_turma')->where('perfil', $perfil)->where('tamanho', '')->get();
            foreach ($vagas as $vaga) {
                DB::table('turmas')->where('id', $vaga->turma_id)->update([$coluna => $vaga->quantidade]);
            }
        }

        Schema::dropIfExists('vagas_turma');
    }
};
