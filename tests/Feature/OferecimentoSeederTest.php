<?php

namespace Tests\Feature;

use App\Models\PeriodoOferecimento;
use Database\Seeders\AtividadeSeeder;
use Database\Seeders\OferecimentoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OferecimentoSeederTest extends TestCase
{
    use RefreshDatabase;

    private function semear(): void
    {
        $this->seed(AtividadeSeeder::class);
        $this->seed(OferecimentoSeeder::class);
    }

    public function test_nenhuma_janela_com_inicio_maior_ou_igual_ao_fim(): void
    {
        $this->semear();

        $this->assertGreaterThan(0, PeriodoOferecimento::count());
        $this->assertSame(0, PeriodoOferecimento::whereColumn('inicio', '>=', 'fim')->count());
        $this->assertSame(72, PeriodoOferecimento::where('perfil', 'curso')->count());
    }

    public function test_janela_conhecida_fica_na_ordem_correta(): void
    {
        $this->semear();

        $usp = PeriodoOferecimento::where(['oferecimento_id' => 97, 'perfil' => 'usp'])->first();
        $this->assertSame('2026-07-14 08:00:00', $usp->inicio->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-15 20:00:00', $usp->fim->format('Y-m-d H:i:s'));

        $curso = PeriodoOferecimento::where(['oferecimento_id' => 97, 'perfil' => 'curso'])->first();
        $this->assertSame('2026-08-03', $curso->inicio->toDateString());
        $this->assertSame('2026-12-11', $curso->fim->toDateString());
    }

    public function test_janela_desativada_nao_gera_registro(): void
    {
        $this->semear();

        $this->assertFalse(PeriodoOferecimento::where(['oferecimento_id' => 33752, 'perfil' => 'usp'])->exists());
    }

    public function test_reexecutar_corrige_linhas_invertidas_e_nao_duplica(): void
    {
        $this->semear();
        $total = PeriodoOferecimento::count();

        PeriodoOferecimento::where(['oferecimento_id' => 97, 'perfil' => 'usp'])->update(['inicio' => '2026-07-15 20:00:00', 'fim' => '2026-07-14 08:00:00']);
        PeriodoOferecimento::create(['oferecimento_id' => 33752, 'perfil' => 'usp', 'inicio' => '2026-01-01', 'fim' => '2026-01-01']);

        $this->seed(OferecimentoSeeder::class);

        $this->assertSame($total, PeriodoOferecimento::count());
        $this->assertSame(0, PeriodoOferecimento::whereColumn('inicio', '>=', 'fim')->count());
    }
}
