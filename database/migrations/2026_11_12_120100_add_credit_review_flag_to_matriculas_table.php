<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->boolean('revisao_credito_sru')->default(false);
            $table->timestamp('revisao_credito_sru_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropColumn(['revisao_credito_sru', 'revisao_credito_sru_em']);
        });
    }
};
