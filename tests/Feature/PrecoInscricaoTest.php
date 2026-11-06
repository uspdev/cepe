<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\Matricula;
use App\Models\Oferecimento;
use App\Models\Perfil;
use App\Models\PeriodoOferecimento;
use App\Models\TaxaTurma;
use App\Models\Turma;
use App\Models\User;
use App\Models\VagaTurma;
use App\Services\PrecoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PrecoInscricaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::define('admin', fn () => false);
        Gate::define('user', fn () => true);
    }

    private function turma(string $tipo = 'semestral', array $taxas = ['tax_in' => 50], string $periodo = 'usp'): Turma
    {
        $atividade = Atividade::create(['nome' => 'Atividade', 'descricao' => 'd', 'tipo' => $tipo, 'status' => 'ativo']);
        $oferecimento = Oferecimento::create(['atividade_id' => $atividade->id, 'periodo' => '01-2026', 'pagamento' => 'pix']);
        PeriodoOferecimento::create(['oferecimento_id' => $oferecimento->id, 'perfil' => $periodo, 'inicio' => now()->subDay(), 'fim' => now()->addDay()]);

        $turma = Turma::create(['oferecimento_id' => $oferecimento->id]);
        VagaTurma::create(['turma_id' => $turma->id, 'perfil' => 'tax_in', 'quantidade' => 10]);
        foreach ($taxas as $perfil => $valor) {
            TaxaTurma::create(['turma_id' => $turma->id, 'perfil' => $perfil, 'valor' => $valor, 'periodo_inscricao' => 1]);
        }

        return $turma;
    }

    private function usuario(array $perfil = []): User
    {
        $user = User::factory()->create();
        Perfil::create($perfil + [
            'user_id' => $user->id,
            'sexo' => 'f',
            'data_nascimento' => '1990-05-10',
            'cpf' => '5299822472' . $user->id,
            'rg' => '123456789',
            'telefone' => '1134567890',
            'emergencia_nome' => 'Maria',
            'emergencia_telefone' => '11987654321',
            'cep' => '05508030',
            'estado' => 'SP',
            'cidade' => 'São Paulo',
            'bairro' => 'Butantã',
            'endereco' => 'Rua do Matão, 1',
            'vinculos_usp' => ['SERVIDOR'],
        ]);

        return $user;
    }

    /** Usuário com carteira de conveniado vigente (paga). */
    private function conveniado(): User
    {
        $usuario = $this->usuario();
        \App\Models\Carteira::create([
            'solicitante_id' => $usuario->id, 'user_id' => $usuario->id, 'categoria' => 'conveniado',
            'nome' => $usuario->name, 'status' => 'ativa', 'numero' => '91631234',
            'validade_inicio' => now()->toDateString(), 'validade_fim' => now()->addYear()->toDateString(),
        ]);

        return $usuario;
    }

    private function preco(Turma $turma, User $user, string $perfil = 'tax_in'): array
    {
        return app(PrecoService::class)->calcular($turma->fresh(['taxas', 'oferecimento.atividade']), $user->fresh(), $perfil, 1);
    }

    private function payload(string $perfil = 'tax_in'): array
    {
        return ['perfil' => $perfil, 'image_use_authorization' => 'autoriza', 'regulamento' => '1'];
    }

    private function matricular(User $user, Turma $turma, string $perfil = 'tax_in'): Matricula
    {
        return Matricula::create([
            'user_id' => $user->id, 'perfil' => $perfil, 'turma_id' => $turma->id,
            'periodo_inscricao' => 1, 'periodo_oferecimento' => '01-2026', 'status' => Matricula::MATRICULADA,
        ]);
    }

    public function test_taxa_base_sem_descontos(): void
    {
        $preco = $this->preco($this->turma(), $this->usuario());

        $this->assertSame(50, $preco['base']);
        $this->assertSame(50, $preco['final']);
        $this->assertSame(0, $preco['desconto']);
    }

    public function test_sem_taxa_configurada_valor_e_nulo(): void
    {
        $this->assertNull($this->preco($this->turma('semestral', []), $this->usuario())['final']);
    }

    public function test_taxa_por_vinculo_so_vale_para_quem_tem_o_vinculo(): void
    {
        $turma = $this->turma('voltausp');
        TaxaTurma::create(['turma_id' => $turma->id, 'perfil' => 'tax_in', 'valor' => 25, 'periodo_inscricao' => 1, 'vinculo' => 'ALUNOGR']);

        $aluno = $this->preco($turma, $this->usuario(['vinculos_usp' => ['ALUNOGR']]));
        $this->assertSame(25, $aluno['final']);
        $this->assertSame('ALUNOGR', $aluno['vinculo']);
        $this->assertSame(25, $aluno['desconto_vinculo']);

        $this->assertSame(50, $this->preco($turma, $this->usuario())['final']);
    }

    public function test_taxa_por_vinculo_maior_que_a_base_e_ignorada(): void
    {
        $turma = $this->turma('voltausp');
        TaxaTurma::create(['turma_id' => $turma->id, 'perfil' => 'tax_in', 'valor' => 80, 'periodo_inscricao' => 1, 'vinculo' => 'ALUNOGR']);

        $this->assertSame(50, $this->preco($turma, $this->usuario(['vinculos_usp' => ['ALUNOGR']]))['final']);
    }

    public function test_tax_con_herda_a_taxa_de_tax_in(): void
    {
        $this->assertSame(50, $this->preco($this->turma(), $this->conveniado(), 'tax_con')['final']);
    }

    public function test_papfe_e_gratuito_em_cursos_mas_nao_em_eventos(): void
    {
        $curso = $this->turma('semestral', ['tax_papfe' => 40]);
        $evento = $this->turma('eventos', ['tax_papfe' => 40]);
        $usuario = $this->usuario(['credito' => 10]);

        $preco = $this->preco($curso, $usuario, 'tax_papfe');
        $this->assertSame(0, $preco['final']);
        $this->assertSame(0, $preco['credito_usado']);

        $this->assertSame(30, $this->preco($evento, $usuario, 'tax_papfe')['final']);
    }

    public function test_desconto_de_segundo_curso_e_piso_de_cinco_reais(): void
    {
        $turma = $this->turma('semestral', ['tax_in' => 50, 'tax_child' => 100, 'tax_ex' => 100]);
        $outra = $this->turma('semestral', ['tax_in' => 50]);
        $usuario = $this->usuario();

        $this->assertSame(100, $this->preco($turma, $usuario, 'tax_child')['final']);

        $this->matricular($usuario, $outra);

        $this->assertSame(50, $this->preco($turma, $usuario, 'tax_child')['final']);
        $this->assertSame(80, $this->preco($turma, $usuario, 'tax_ex')['final']);
        $this->assertSame(50, $this->preco($turma, $usuario, 'tax_in')['final']);

        // 6 - 5 (piso do desconto) = 1, elevado ao mínimo de R$5; a diferença vira crédito
        $barata = $this->turma('semestral', ['tax_child' => 6]);
        $preco = $this->preco($barata, $usuario, 'tax_child');
        $this->assertSame(5, $preco['final']);
        $this->assertSame(-4, $preco['credito_usado']);
    }

    public function test_segundo_curso_nao_se_aplica_a_eventos_nem_a_conveniado_ex(): void
    {
        $evento = $this->turma('eventos', ['tax_child' => 100]);
        $curso = $this->turma('semestral', ['tax_ex' => 100]);
        $usuario = $this->conveniado();
        $this->matricular($usuario, $this->turma('semestral'));

        $this->assertSame(100, $this->preco($evento, $usuario, 'tax_child')['final']);
        $this->assertSame(100, $this->preco($curso, $usuario, 'tax_ex')['final']);
    }

    public function test_credito_parcial_total_e_piso(): void
    {
        $turma = $this->turma();

        $parcial = $this->preco($turma, $this->usuario(['credito' => 20]));
        $this->assertSame(30, $parcial['final']);
        $this->assertSame(20, $parcial['credito_usado']);

        $total = $this->preco($turma, $this->usuario(['credito' => 80]));
        $this->assertSame(0, $total['final']);
        $this->assertSame(50, $total['credito_usado']);

        $piso = $this->preco($turma, $this->usuario(['credito' => 48]));
        $this->assertSame(5, $piso['final']);
        $this->assertSame(45, $piso['credito_usado']);

        // taxa abaixo do mínimo: cobra o mínimo e a diferença vira crédito (regra do legado)
        $barata = $this->preco($this->turma('semestral', ['tax_in' => 3]), $this->usuario(['credito' => 2]));
        $this->assertSame(5, $barata['final']);
        $this->assertSame(-2, $barata['credito_usado']);

        $semCredito = $this->preco($this->turma('semestral', ['tax_in' => 3]), $this->usuario());
        $this->assertSame(5, $semCredito['final']);
        $this->assertSame(-2, $semCredito['credito_usado']);
    }

    public function test_oferecimento_gratuito_zera_a_taxa_de_todos_os_perfis(): void
    {
        $turma = $this->turma('eventos', ['tax_in' => 50]);
        $turma->oferecimento->update(['pagamento' => serialize(['gratuito'])]);
        $usuario = $this->usuario(['credito' => 20]);

        $preco = $this->preco($turma, $usuario);
        $this->assertSame(0, $preco['final']);
        $this->assertSame(0, $preco['credito_usado']);
        $this->assertSame(50, $preco['desconto']);

        $turma->oferecimento->update(['pagamento' => 'gratuito']);
        $this->assertSame(0, $this->preco($turma, $usuario)['final']);
    }

    public function test_credito_gerado_e_estornado_no_cancelamento_sem_ficar_negativo(): void
    {
        $turma = $this->turma('semestral', ['tax_in' => 3]);
        $usuario = $this->usuario(['credito' => 0]);

        $this->actingAs($usuario)->post("/inscricoes/{$turma->id}", $this->payload())->assertSessionHasNoErrors();
        $matricula = Matricula::first();
        $this->assertSame(5, $matricula->valor);
        $this->assertSame(-2, $matricula->credito_usado);
        $this->assertSame(2, $usuario->fresh()->perfil->credito);

        Perfil::where('user_id', $usuario->id)->update(['credito' => 0]);
        $this->actingAs($usuario)->patch("/minhas-inscricoes/{$matricula->id}/cancelar");
        $this->assertSame(0, $usuario->fresh()->perfil->credito);
    }

    public function test_taxa_por_vinculo_so_vale_para_voltausp(): void
    {
        foreach (['semestral', 'eventos', 'voltinhausp'] as $tipo) {
            $turma = $this->turma($tipo);
            TaxaTurma::create(['turma_id' => $turma->id, 'perfil' => 'tax_in', 'valor' => 25, 'periodo_inscricao' => 1, 'vinculo' => 'ALUNOGR']);

            $preco = $this->preco($turma, $this->usuario(['vinculos_usp' => ['ALUNOGR']]));
            $this->assertSame(50, $preco['final'], $tipo);
            $this->assertNull($preco['vinculo'], $tipo);
        }
    }

    public function test_inscricao_grava_valores_debita_credito_e_cancelamento_estorna(): void
    {
        $turma = $this->turma('voltausp');
        TaxaTurma::create(['turma_id' => $turma->id, 'perfil' => 'tax_in', 'valor' => 30, 'periodo_inscricao' => 1, 'vinculo' => 'ALUNOGR']);
        $usuario = $this->usuario(['vinculos_usp' => ['ALUNOGR'], 'credito' => 10]);

        $this->actingAs($usuario)->get("/inscricoes/{$turma->id}")->assertOk();
        $this->actingAs($usuario)->post("/inscricoes/{$turma->id}", $this->payload())->assertSessionHasNoErrors();

        $matricula = Matricula::first();
        $this->assertSame(50, $matricula->valor_base);
        $this->assertSame(30, $matricula->valor + $matricula->credito_usado);
        $this->assertSame(20, $matricula->valor);
        $this->assertSame(10, $matricula->credito_usado);
        $this->assertSame(20, $matricula->desconto);
        $this->assertSame('ALUNOGR', $matricula->metadado('vinculo_desconto'));
        $this->assertSame(0, $usuario->fresh()->perfil->credito);

        $this->actingAs($usuario)->patch("/minhas-inscricoes/{$matricula->id}/cancelar");
        $this->assertSame(10, $usuario->fresh()->perfil->credito);

        $this->actingAs($usuario)->patch("/minhas-inscricoes/{$matricula->id}/cancelar")->assertStatus(422);
        $this->assertSame(10, $usuario->fresh()->perfil->credito);
    }

    public function test_tela_de_inscricao_mostra_valor_com_desconto(): void
    {
        $turma = $this->turma();
        $usuario = $this->usuario(['credito' => 20]);

        $html = $this->actingAs($usuario)->get("/inscricoes/{$turma->id}")->assertOk()->getContent();

        // o cartão oculto de fila vem antes do formulário: confere só o que está depois do marcador do formulário
        $formulario = substr($html, strpos($html, 'id="secao-formulario-inscricao"'));
        $this->assertStringContainsString('R$ 30,00', $formulario);
        $this->assertStringContainsString('crédito', $formulario);
        $this->assertStringContainsString('taxa R$ 50,00', $formulario);
    }

    public function test_formulario_mostra_o_preco_mesmo_com_perfil_ja_gravado_na_fila(): void
    {
        $turma = $this->turma();
        $usuario = $this->usuario();

        // 1ª visita grava o perfil único na fila; a 2ª renderiza o ramo "perfil travado"
        $this->actingAs($usuario)->get("/inscricoes/{$turma->id}");
        $html = $this->actingAs($usuario)->get("/inscricoes/{$turma->id}")->getContent();

        $formulario = substr($html, strpos($html, 'id="secao-formulario-inscricao"'));
        $this->assertStringContainsString('R$ 50,00', $formulario);
    }

    public function test_calculo_de_preco_nao_consulta_segundo_curso_para_perfis_sem_desconto(): void
    {
        $turma = $this->turma('semestral', ['tax_in' => 50, 'tax_child' => 100]);
        $usuario = $this->usuario();
        $this->matricular($usuario, $this->turma('semestral'));

        \DB::enableQueryLog();
        $this->preco($turma, $usuario, 'tax_in');
        $semDesconto = collect(\DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'matriculas'))->count();
        \DB::flushQueryLog();
        $this->preco($turma, $usuario, 'tax_child');
        $comDesconto = collect(\DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'matriculas'))->count();

        $this->assertSame(0, $semDesconto);
        $this->assertGreaterThan(0, $comDesconto);
    }
}
