<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\Cobranca;
use App\Models\Matricula;
use App\Models\Oferecimento;
use App\Models\Perfil;
use App\Models\PeriodoOferecimento;
use App\Models\TaxaTurma;
use App\Models\Turma;
use App\Models\User;
use App\Models\VagaTurma;
use App\Services\CobrancaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BoletoInscricaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::define('admin', fn () => false);
        Gate::define('user', fn () => true);
    }

    private function turma(int $valor = 50, string $tipo = 'semestral', array $formas = ['boleto']): Turma
    {
        $atividade = Atividade::create([
            'nome' => 'Atividade de teste',
            'descricao' => 'Descrição',
            'tipo' => $tipo,
            'status' => 'ativo',
        ]);
        $oferecimento = Oferecimento::create([
            'atividade_id' => $atividade->id,
            'periodo' => '01-2026',
            'pagamento' => serialize($formas),
        ]);
        PeriodoOferecimento::create([
            'oferecimento_id' => $oferecimento->id,
            'perfil' => 'usp',
            'inicio' => now()->subDay(),
            'fim' => now()->addDay(),
        ]);

        $turma = Turma::create(['oferecimento_id' => $oferecimento->id]);
        VagaTurma::create(['turma_id' => $turma->id, 'perfil' => 'tax_in', 'quantidade' => 5]);
        TaxaTurma::create([
            'turma_id' => $turma->id,
            'perfil' => 'tax_in',
            'valor' => $valor,
            'periodo_inscricao' => 1,
        ]);

        return $turma;
    }

    private function usuario(int $credito = 0): User
    {
        $user = User::factory()->create();
        Perfil::create([
            'user_id' => $user->id,
            'sexo' => 'f',
            'data_nascimento' => '1990-05-10',
            'cpf' => $this->cpfValido($user->id),
            'rg' => '123456789',
            'telefone' => '1134567890',
            'emergencia_nome' => 'Maria',
            'emergencia_telefone' => '11987654321',
            'cep' => '05508030',
            'estado' => 'SP',
            'cidade' => 'São Paulo',
            'bairro' => 'Butantã',
            'endereco' => 'Rua do Matão, 1',
            'credito' => $credito,
            'vinculos_usp' => ['SERVIDOR'],
        ]);

        return $user;
    }

    private function cpfValido(int $id): string
    {
        $cpf = str_pad((string) $id, 9, '0', STR_PAD_LEFT);
        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int) $cpf[$i] * (($t + 1) - $i);
            }
            $cpf .= (string) (((10 * $soma) % 11) % 10);
        }

        return $cpf;
    }

    private function inscrever(User $user, Turma $turma, string $metodo = 'boleto'): void
    {
        $this->actingAs($user)->get("/inscricoes/{$turma->id}")->assertOk();
        $this->actingAs($user)->post("/inscricoes/{$turma->id}", [
            'perfil' => 'tax_in',
            'metodo_pagamento' => $metodo,
            'image_use_authorization' => 'autoriza',
            'regulamento' => '1',
        ])->assertRedirect('/minhas-inscricoes')->assertSessionHasNoErrors();
    }

    public function test_inscricao_paga_reserva_vaga_e_emite_boleto(): void
    {
        $turma = $this->turma();
        $turma->oferecimento->update(['pagamento' => serialize(['pix', 'boleto'])]);
        $user = $this->usuario();

        $this->actingAs($user)->get("/inscricoes/{$turma->id}")
            ->assertOk()
            ->assertSee('Boleto bancário')
            ->assertSee('Pix');
        $this->inscrever($user, $turma);

        $matricula = Matricula::firstOrFail();
        $cobranca = $matricula->cobrancas()->firstOrFail();

        $this->assertSame(Matricula::AGUARDANDO_PAGAMENTO, $matricula->status);
        $this->assertSame(Cobranca::EMITIDA, $cobranca->status);
        $this->assertSame(5000, $cobranca->valor_centavos);
        $this->assertSame(1, Matricula::ativas()->where('turma_id', $turma->id)->count());
        $this->assertSame('PF', $this->boleto->dadosGerados[0]['tipoSacado']);
        $this->assertSame('50.00', $this->boleto->dadosGerados[0]['valorDocumento']);
    }

    public function test_curso_acima_de_trezentos_emite_tres_parcelas_sequenciais(): void
    {
        $turma = $this->turma(301);
        $user = $this->usuario();
        $this->inscrever($user, $turma);

        $matricula = Matricula::firstOrFail();
        $primeira = $matricula->cobrancas()->firstOrFail();
        $this->assertSame(10034, $primeira->valor_centavos);
        $this->assertSame(3, $primeira->total_parcelas);
        $this->assertSame(1, $matricula->cobrancas()->count());

        $this->boleto->marcarPago($primeira->codigo_boleto);
        app(CobrancaService::class)->sincronizar($primeira);

        $matricula->refresh();
        $this->assertSame(Matricula::MATRICULADA, $matricula->status);
        $segunda = $matricula->cobrancas()->where('parcela', 2)->firstOrFail();
        $this->assertSame(10033, $segunda->valor_centavos);
        $this->assertSame(
            $primeira->vencimento->copy()->addMonthNoOverflow()->toDateString(),
            $segunda->vencimento->toDateString(),
        );

        $this->boleto->marcarPago($segunda->codigo_boleto);
        app(CobrancaService::class)->sincronizar($segunda);
        $terceira = $matricula->cobrancas()->where('parcela', 3)->firstOrFail();
        $this->assertSame(10033, $terceira->valor_centavos);
        $this->assertSame(30100, $matricula->cobrancas()->sum('valor_centavos'));
    }

    public function test_curso_ate_trezentos_emite_boleto_unico(): void
    {
        $turma = $this->turma(300);
        $user = $this->usuario();
        $this->inscrever($user, $turma);

        $cobranca = Cobranca::firstOrFail();
        $this->assertSame(1, $cobranca->total_parcelas);
        $this->assertSame(30000, $cobranca->valor_centavos);
    }

    public function test_voltausp_e_voltinhausp_usam_configuracao_financeira_de_eventos(): void
    {
        foreach (['eventos', 'voltausp', 'voltinhausp'] as $tipo) {
            $turma = $this->turma(50, $tipo);
            $user = $this->usuario();
            $matricula = Matricula::create([
                'user_id' => $user->id,
                'perfil' => 'tax_in',
                'turma_id' => $turma->id,
                'periodo_inscricao' => 1,
                'periodo_oferecimento' => '01-2026',
                'status' => Matricula::AGUARDANDO_PAGAMENTO,
                'valor_base' => 50,
                'desconto' => 0,
                'valor' => 50,
                'credito_usado' => 0,
            ]);

            $cobranca = app(CobrancaService::class)->emitirPrimeira($matricula);

            $this->assertSame(595, $this->boleto->dadosGerados[array_key_last($this->boleto->dadosGerados)]['codigoFonteRecurso'], $tipo);
            $this->assertSame(Carbon::today(config('cepe.fuso_frequencia'))->addDay()->toDateString(), $cobranca->vencimento->toDateString(), $tipo);
        }
    }

    public function test_pix_only_gera_qr_code_e_ativa_matricula_apos_confirmacao(): void
    {
        $turma = $this->turma(350, 'semestral', ['pix']);
        $user = $this->usuario();

        $this->inscrever($user, $turma, 'pix');

        $matricula = Matricula::firstOrFail();
        $pix = $matricula->cobrancas()->firstOrFail();

        $this->assertSame(Matricula::AGUARDANDO_PAGAMENTO, $matricula->status);
        $this->assertSame(Cobranca::PIX, $pix->metodo);
        $this->assertSame(1, $pix->total_parcelas);
        $this->assertNotNull($pix->id_pix);
        $this->assertNotNull($pix->pix_copia_cola);
        $this->assertNotNull($pix->pix_qr_code_img_base64);
        $this->assertEqualsWithDelta(10800, $pix->emitido_em->diffInSeconds($pix->expira_em), 5);

        $this->pix->marcarPago($pix->id_pix);
        app(CobrancaService::class)->sincronizar($pix);

        $this->assertSame(Matricula::MATRICULADA, $matricula->fresh()->status);
    }

    public function test_pix_expirado_cancela_matricula_e_estorna_credito(): void
    {
        $turma = $this->turma(50, 'semestral', ['pix']);
        $user = $this->usuario(10);
        $this->inscrever($user, $turma, 'pix');

        $matricula = Matricula::firstOrFail();
        $pix = $matricula->cobrancas()->firstOrFail();
        $this->pix->marcarExpirado($pix->id_pix);
        app(CobrancaService::class)->sincronizar($pix);

        $this->assertSame(Matricula::CANCELADA, $matricula->fresh()->status);
        $this->assertSame(Cobranca::VENCIDA, $pix->fresh()->status);
        $this->assertSame(10, $user->fresh()->perfil->credito);
        $this->assertSame(0, Matricula::ativas()->where('turma_id', $turma->id)->count());
    }

    public function test_falha_definitiva_de_emissao_cancela_reserva_e_estorna_credito(): void
    {
        $turma = $this->turma();
        $user = $this->usuario(10);
        $this->boleto->resultadoIndeterminadoNaEmissao = false;

        $this->actingAs($user)->get("/inscricoes/{$turma->id}")->assertOk();
        $this->actingAs($user)->post("/inscricoes/{$turma->id}", [
            'perfil' => 'tax_in',
            'metodo_pagamento' => 'boleto',
            'image_use_authorization' => 'autoriza',
            'regulamento' => '1',
        ])->assertSessionHasErrors('metodo_pagamento');

        $this->assertSame(Matricula::CANCELADA, Matricula::firstOrFail()->status);
        $this->assertSame(0, Matricula::ativas()->where('turma_id', $turma->id)->count());
        $this->assertSame(10, $user->fresh()->perfil->credito);
        $this->assertSame(Cobranca::FALHA_EMISSAO, Cobranca::firstOrFail()->status);
    }

    public function test_emissao_indeterminada_mantem_a_vaga_reservada_sem_repetir_a_chamada(): void
    {
        $turma = $this->turma();
        $user = $this->usuario();
        $this->boleto->resultadoIndeterminadoNaEmissao = true;

        $this->actingAs($user)->get("/inscricoes/{$turma->id}")->assertOk();
        $this->actingAs($user)->post("/inscricoes/{$turma->id}", [
            'perfil' => 'tax_in',
            'metodo_pagamento' => 'boleto',
            'image_use_authorization' => 'autoriza',
            'regulamento' => '1',
        ])->assertRedirect('/minhas-inscricoes');

        $this->assertSame(Matricula::AGUARDANDO_PAGAMENTO, Matricula::firstOrFail()->status);
        $this->assertSame(Cobranca::EMISSAO_INDETERMINADA, Cobranca::firstOrFail()->status);
        $this->assertSame(1, Cobranca::firstOrFail()->tentativas_emissao);
        $this->assertSame(1, Matricula::ativas()->where('turma_id', $turma->id)->count());
        $this->assertCount(0, $this->boleto->dadosGerados);
    }

    public function test_cancelamento_apos_primeiro_pagamento_cancela_a_parcela_seguinte(): void
    {
        $turma = $this->turma(321);
        $user = $this->usuario(20);
        $this->inscrever($user, $turma);

        $matricula = Matricula::firstOrFail();
        $primeira = $matricula->cobrancas()->firstOrFail();
        $this->boleto->marcarPago($primeira->codigo_boleto);
        app(CobrancaService::class)->sincronizar($primeira);
        $segunda = $matricula->cobrancas()->where('parcela', 2)->firstOrFail();

        $this->actingAs($user)->patch("/minhas-inscricoes/{$matricula->id}/cancelar")->assertRedirect('/minhas-inscricoes');

        $this->assertSame(Matricula::CANCELADA, $matricula->fresh()->status);
        $this->assertTrue($matricula->fresh()->revisao_credito_sru);
        $this->assertSame(Cobranca::CANCELADA, $segunda->fresh()->status);
        $this->assertSame(0, $user->fresh()->perfil->credito);
    }

    public function test_vencimento_de_parcela_posterior_cancela_matricula_e_sinaliza_sru(): void
    {
        $turma = $this->turma(321);
        $user = $this->usuario(credito: 20);
        $this->inscrever($user, $turma);

        $matricula = Matricula::firstOrFail();
        $primeira = $matricula->cobrancas()->firstOrFail();
        $this->boleto->marcarPago($primeira->codigo_boleto);
        app(CobrancaService::class)->sincronizar($primeira);

        $segunda = $matricula->cobrancas()->where('parcela', 2)->firstOrFail();
        $segunda->update(['vencimento' => today()->subDay()]);
        app(CobrancaService::class)->sincronizar($segunda);

        $matricula->refresh();
        $this->assertSame(Matricula::CANCELADA, $matricula->status);
        $this->assertTrue($matricula->revisao_credito_sru);
        $this->assertSame(0, $user->fresh()->perfil->credito);
        $this->assertSame(Cobranca::VENCIDA, $segunda->fresh()->status);

        Permission::findOrCreate('pagamentos.gerenciar', 'web');
        $sru = User::factory()->create();
        $sru->givePermissionTo('pagamentos.gerenciar');

        $this->actingAs($sru)->get('/boletos?revisao_credito_sru=1')
            ->assertOk()
            ->assertSee('Revisão de crédito SRU');
    }

    public function test_pdf_do_boleto_fica_restrito_ao_titular_ou_sru(): void
    {
        $turma = $this->turma();
        $user = $this->usuario();
        $this->inscrever($user, $turma);
        $matricula = Matricula::firstOrFail();
        $cobranca = $matricula->cobrancas()->firstOrFail();

        $this->actingAs($user)
            ->get(route('minhas-inscricoes.boletos.pdf', [$matricula, $cobranca]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertSee('%PDF-1.4 teste', false);

        $outro = $this->usuario();
        $this->actingAs($outro)
            ->get(route('minhas-inscricoes.boletos.pdf', [$matricula, $cobranca]))
            ->assertForbidden();

        $this->actingAs($outro)->get('/boletos')->assertForbidden();
    }
}
