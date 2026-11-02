<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\Oferecimento;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TurmaTaxasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::define('admin', fn () => true);
        Gate::define('user', fn () => true);
    }

    private function turma(string $tipo): Turma
    {
        $atividade = Atividade::create(['nome' => 'Atividade', 'descricao' => 'd', 'tipo' => $tipo, 'status' => 'ativo']);
        $oferecimento = Oferecimento::create(['atividade_id' => $atividade->id, 'periodo' => '01-2026', 'pagamento' => 'pix']);

        return Turma::create(['oferecimento_id' => $oferecimento->id]);
    }

    private function salvar(Turma $turma, array $taxas)
    {
        return $this->actingAs(User::factory()->create())
            ->patch("/turmas/{$turma->id}", ['oferecimento_id' => $turma->oferecimento_id, 'taxas' => $taxas]);
    }

    private function taxas(): array
    {
        return [
            'tax_in_1' => ['perfil' => 'tax_in', 'periodo_inscricao' => 1, 'valor' => 50],
            'tax_in_1_ALUNOGR' => ['perfil' => 'tax_in', 'periodo_inscricao' => 1, 'valor' => 25, 'vinculo' => 'ALUNOGR'],
            'tax_ex_1' => ['perfil' => 'tax_ex', 'periodo_inscricao' => 1, 'valor' => 0],
        ];
    }

    public function test_voltausp_grava_taxa_por_vinculo_e_taxa_zero(): void
    {
        $turma = $this->turma('voltausp');

        $this->salvar($turma, $this->taxas())->assertSessionHasNoErrors();

        $taxas = $turma->fresh()->taxas;
        $this->assertSame(3, $taxas->count());
        $this->assertSame(25, $taxas->where('perfil', 'tax_in')->where('vinculo', 'ALUNOGR')->first()->valor);
        $this->assertSame(50, $taxas->where('perfil', 'tax_in')->where('vinculo', '')->first()->valor);
        $this->assertSame(0, $taxas->where('perfil', 'tax_ex')->first()->valor);
    }

    public function test_outros_tipos_ignoram_taxa_por_vinculo(): void
    {
        $turma = $this->turma('semestral');

        $this->salvar($turma, $this->taxas())->assertSessionHasNoErrors();

        $taxas = $turma->fresh()->taxas;
        $this->assertSame(2, $taxas->count());
        $this->assertSame([''], $taxas->pluck('vinculo')->unique()->values()->all());
    }

    public function test_vinculo_desconhecido_e_rejeitado(): void
    {
        $turma = $this->turma('voltausp');
        $taxas = ['x' => ['perfil' => 'tax_in', 'periodo_inscricao' => 1, 'valor' => 10, 'vinculo' => 'INEXISTENTE']];

        $this->salvar($turma, $taxas)->assertSessionHasErrors();
        $this->assertSame(0, $turma->fresh()->taxas->count());
    }

    public function test_taxa_por_vinculo_exige_a_taxa_geral_do_mesmo_perfil_e_periodo(): void
    {
        $turma = $this->turma('voltausp');
        $soVinculo = ['tax_in_1_ALUNOGR' => ['perfil' => 'tax_in', 'periodo_inscricao' => 1, 'valor' => 25, 'vinculo' => 'ALUNOGR']];

        $this->salvar($turma, $soVinculo)->assertSessionHasErrors('taxas.tax_in_1_ALUNOGR.valor');

        $outroPeriodo = $soVinculo + ['tax_in_2' => ['perfil' => 'tax_in', 'periodo_inscricao' => 2, 'valor' => 50]];
        $this->salvar($turma, $outroPeriodo)->assertSessionHasErrors('taxas.tax_in_1_ALUNOGR.valor');

        $this->salvar($turma, $soVinculo + ['tax_in_1' => ['perfil' => 'tax_in', 'periodo_inscricao' => 1, 'valor' => 50]])
            ->assertSessionHasNoErrors();
        $this->assertSame(2, $turma->fresh()->taxas->count());
    }

    public function test_taxa_vazia_com_vinculo_nao_exige_taxa_geral(): void
    {
        $turma = $this->turma('voltausp');
        $vazia = ['tax_in_1_ALUNOGR' => ['perfil' => 'tax_in', 'periodo_inscricao' => 1, 'valor' => '', 'vinculo' => 'ALUNOGR']];

        $this->salvar($turma, $vazia)->assertSessionHasNoErrors();
        $this->assertSame(0, $turma->fresh()->taxas->count());
    }

    public function test_taxa_decimal_e_rejeitada(): void
    {
        $turma = $this->turma('semestral');

        $this->salvar($turma, ['tax_in_1' => ['perfil' => 'tax_in', 'periodo_inscricao' => 1, 'valor' => '12.5']])
            ->assertSessionHasErrors('taxas.tax_in_1.valor');
        $this->assertSame(0, $turma->fresh()->taxas->count());
    }

    public function test_formulario_so_mostra_taxa_por_vinculo_no_voltausp(): void
    {
        $voltausp = $this->turma('voltausp');
        $curso = $this->turma('semestral');

        $this->actingAs(User::factory()->create())->get("/turmas/{$voltausp->id}/edit")
            ->assertOk()->assertSee('Aluno de Graduação', false);
        $this->actingAs(User::factory()->create())->get("/turmas/{$curso->id}/edit")
            ->assertOk()->assertDontSee('Aluno de Graduação', false);
    }
}
