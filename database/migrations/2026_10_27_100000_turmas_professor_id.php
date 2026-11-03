<?php

use App\Support\Papeis;
use Database\Seeders\ProfessorSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turmas', function (Blueprint $table) {
            $table->unsignedBigInteger('professor_id')->nullable()->after('horario_fim');
            $table->foreign('professor_id')->references('id')->on('users')->nullOnDelete();
        });

        if (DB::table('turmas')->whereNotNull('professor')->exists()) {
            Papeis::sincronizar();
            (new ProfessorSeeder)->run();

            DB::table('turmas')->whereNotNull('professor')->get(['id', 'professor'])->each(function ($turma) {
                DB::table('turmas')->where('id', $turma->id)->update([
                    'professor_id' => ProfessorSeeder::idPorNome((string) $turma->professor),
                ]);
            });
        }

        $temFkProfessor = collect(Schema::getForeignKeys('turmas'))->contains(fn ($fk) => $fk['columns'] === ['professor']);

        Schema::table('turmas', function (Blueprint $table) use ($temFkProfessor) {
            if ($temFkProfessor) {
                $table->dropForeign(['professor']);
            }
            $table->dropColumn('professor');
        });
    }

    public function down(): void
    {
        Schema::table('turmas', function (Blueprint $table) {
            $table->string('professor')->nullable()->after('horario_fim');
            $table->dropForeign(['professor_id']);
            $table->dropColumn('professor_id');
        });
    }
};
