<?php

namespace Tests\Feature;

use App\Models\Carteira;
use App\Models\SolicitacaoCarteira;
use App\Models\Perfil;
use App\Models\User;
use App\Services\InscricaoService;
use App\Support\Papeis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CarteiraTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Gate::define('admin', fn ($u) => $u->email === 'admin@teste.com');
        Gate::define('user', fn () => true);
        Papeis::sincronizar();
    }

    private function usuario(array $perfil = [], ?string $papel = null, array $user = []): User
    {
        $usuario = User::factory()->create($user);
        Perfil::create(['user_id' => $usuario->id] + $perfil);
        $papel && $usuario->assignRole($papel);

        return $usuario->fresh();
    }

    private function servidor(): User
    {
        return $this->usuario(['vinculos_usp' => ['SERVIDOR']]);
    }

    private function arquivos(array $tipos): array
    {
        return collect($tipos)->mapWithKeys(fn ($tipo) => [$tipo => $tipo === 'foto'
            ? UploadedFile::fake()->image('foto.jpg')
            : UploadedFile::fake()->create("{$tipo}.pdf", 100, 'application/pdf')])->all();
    }

    private function solicitarConjuge(User $titular, array $extra = [])
    {
        return $this->actingAs($titular)->post('/minhas-carteiras', $extra + [
            'categoria' => 'dependente_servidor',
            'nome' => 'Maria da Silva',
            'cpf' => '529.982.247-25',
            'data_nascimento' => '10/05/1985',
            'parentesco' => 'conjuge',
            'documentos' => $this->arquivos(['holerite', 'carteira_usp', 'foto', 'certidao_casamento', 'rg']),
        ]);
    }

    /** Carteira aprovada e paga (status com benefício), direto no banco. */
    private function ativa(User $titular, array $dados = []): Carteira
    {
        return Carteira::create($dados + [
            'user_id' => $titular->id,
            'categoria' => 'dependente_servidor',
            'nome' => 'Maria da Silva', 'cpf' => '52998224725', 'data_nascimento' => '1985-05-10', 'parentesco' => 'conjuge',
            'status' => 'ativa', 'numero' => '90631234', 'via' => 1,
            'validade_inicio' => now()->toDateString(), 'validade_fim' => now()->addYear()->toDateString(),
        ]);
    }

    public function test_titular_servidor_solicita_carteira_do_conjuge(): void
    {
        $titular = $this->servidor();

        $this->solicitarConjuge($titular)->assertSessionHasNoErrors()->assertRedirect();

        $solicitacao = SolicitacaoCarteira::with('documentos')->firstOrFail();
        $this->assertSame('em_analise', $solicitacao->status);
        $this->assertSame('52998224725', $solicitacao->cpf);
        $this->assertSame($titular->id, $solicitacao->solicitante_id);
        $this->assertNull($solicitacao->carteira_id);
        $this->assertSame(0, Carteira::count());
        $this->assertEqualsCanonicalizing(['holerite', 'carteira_usp', 'foto', 'certidao_casamento', 'rg'], $solicitacao->documentos->pluck('tipo')->all());
        $solicitacao->documentos->each(fn ($d) => Storage::disk('local')->assertExists($d->arquivo));
    }

    public function test_documento_obrigatorio_do_parentesco_e_foto_em_jpg(): void
    {
        $titular = $this->servidor();
        $documentos = $this->arquivos(['holerite', 'carteira_usp', 'certidao_casamento']);
        $documentos['foto'] = UploadedFile::fake()->create('foto.pdf', 10, 'application/pdf');

        $this->solicitarConjuge($titular, ['documentos' => $documentos])
            ->assertSessionHasErrors(['documentos.rg', 'documentos.foto']);
        $this->assertSame(0, SolicitacaoCarteira::count());
    }

    public function test_idade_do_filho_define_o_parentesco(): void
    {
        $titular = $this->servidor();
        $base = ['nome' => 'Filho', 'cpf' => null];

        $this->solicitarConjuge($titular, $base + [
            'parentesco' => 'filho', 'data_nascimento' => now()->subYears(23)->format('d/m/Y'),
            'documentos' => $this->arquivos(['holerite', 'carteira_usp', 'foto', 'certidao_nascimento_ou_rg']),
        ])->assertSessionHasErrors('data_nascimento');

        $this->solicitarConjuge($titular, $base + [
            'parentesco' => 'filho_universitario', 'data_nascimento' => now()->subYears(25)->format('d/m/Y'),
            'documentos' => $this->arquivos(['holerite', 'carteira_usp', 'foto', 'rg', 'declaracao_faculdade']),
        ])->assertSessionHasErrors('data_nascimento');

        $this->solicitarConjuge($titular, $base + [
            'parentesco' => 'filho_universitario', 'data_nascimento' => now()->subYears(23)->format('d/m/Y'),
            'documentos' => $this->arquivos(['holerite', 'carteira_usp', 'foto', 'rg']),
        ])->assertSessionHasErrors('documentos.declaracao_faculdade');

        $this->assertSame(0, SolicitacaoCarteira::count());
    }

    public function test_categoria_exige_vinculo_do_titular(): void
    {
        $semVinculo = $this->usuario();

        $this->solicitarConjuge($semVinculo)->assertSessionHasErrors('categoria');
        $this->solicitarConjuge($this->servidor(), ['categoria' => 'dependente_aluno'])->assertSessionHasErrors('categoria');
        $this->actingAs($semVinculo)->get('/minhas-carteiras/create?categoria=dependente_servidor')->assertNotFound();
        $this->actingAs($semVinculo)->get('/minhas-carteiras/create?categoria=ex_aluno')->assertOk();
    }

    public function test_dependente_de_aposentado_exige_carteira_de_aposentado_vigente(): void
    {
        $aposentado = $this->usuario();
        $servico = app(\App\Services\CarteiraService::class);
        $this->assertArrayNotHasKey('dependente_aposentado', $servico->categoriasPermitidas($aposentado));

        // aprovada mas ainda sem pagamento: não libera a categoria
        $this->ativa($aposentado, ['categoria' => 'aposentado', 'parentesco' => null, 'numero' => '555', 'status' => 'aprovada']);
        $this->assertArrayNotHasKey('dependente_aposentado', $servico->categoriasPermitidas($aposentado));

        Carteira::query()->update(['status' => 'em_confeccao']);
        $this->assertArrayHasKey('dependente_aposentado', $servico->categoriasPermitidas($aposentado));
    }

    public function test_somente_sru_analisa_e_terceiros_nao_veem_documentos(): void
    {
        $titular = $this->servidor();
        $this->solicitarConjuge($titular);
        $solicitacao = SolicitacaoCarteira::with('documentos')->firstOrFail();
        $documento = $solicitacao->documentos->first();

        $this->actingAs($this->usuario())->get('/carteiras')->assertForbidden();
        $this->actingAs($this->usuario(papel: 'gestor-cursos'))->get("/carteiras/{$solicitacao->id}")->assertForbidden();
        $this->actingAs($this->usuario())->get("/minhas-carteiras/{$solicitacao->id}")->assertForbidden();
        $this->actingAs($this->usuario())->get("/carteiras/{$solicitacao->id}/documentos/{$documento->id}")->assertForbidden();

        $this->actingAs($titular)->get("/carteiras/{$solicitacao->id}/documentos/{$documento->id}")->assertOk();
        $sru = $this->usuario(papel: 'sru');
        $this->actingAs($sru)->get('/carteiras')->assertOk()->assertSee('Maria da Silva');
        $this->actingAs($sru)->get("/carteiras/{$solicitacao->id}")->assertOk();
        $this->actingAs($sru)->get("/carteiras/{$solicitacao->id}/documentos/{$documento->id}")->assertOk();
    }

    public function test_aprovacao_exige_numero_unico_com_prefixo_e_define_validade(): void
    {
        $titular = $this->servidor();
        $this->solicitarConjuge($titular);
        $solicitacao = SolicitacaoCarteira::firstOrFail();
        $this->ativa($this->servidor(), ['numero' => '90639999']);
        $sru = $this->usuario(papel: 'sru');
        $url = "/carteiras/{$solicitacao->id}/analise";

        $this->actingAs($sru)->patch($url, ['status' => 'aprovada'])->assertSessionHasErrors('numero');
        $this->actingAs($sru)->patch($url, ['status' => 'aprovada', 'numero' => '91631234'])->assertSessionHasErrors('numero');
        $this->actingAs($sru)->patch($url, ['status' => 'aprovada', 'numero' => '90639999'])->assertSessionHasErrors('numero');
        $this->actingAs($sru)->patch($url, ['status' => 'reprovada'])->assertSessionHasErrors('observacao');
        $this->actingAs($this->usuario(papel: 'gestor-cursos'))->patch($url, ['status' => 'aprovada', 'numero' => '90631234'])->assertForbidden();

        $this->actingAs($sru)->patch($url, ['status' => 'aprovada', 'numero' => '90631234', 'via' => 1])->assertSessionHasNoErrors();

        $solicitacao->refresh()->load('carteira');
        $this->assertSame('aprovada', $solicitacao->status);
        $carteira = $solicitacao->carteira;
        $this->assertSame('90631234', $carteira->numero);
        $this->assertSame(1, $carteira->via);
        $this->assertSame(today()->toDateString(), $carteira->validade_inicio->toDateString());
        $this->assertSame(today()->addYear()->toDateString(), $carteira->validade_fim->toDateString());
        $this->assertSame($sru->id, $solicitacao->analisado_por);
        $this->assertNull($carteira->user_id);
    }

    public function test_aprovacao_de_categoria_propria_nao_vincula_automaticamente(): void
    {
        $exAluno = $this->usuario(['cpf' => '52998224725', 'data_nascimento' => '1980-01-01']);
        $this->actingAs($exAluno)->post('/minhas-carteiras', [
            'categoria' => 'ex_aluno',
            'documentos' => $this->arquivos(['diploma', 'rg_cnh', 'foto']),
        ])->assertSessionHasNoErrors();
        $solicitacao = SolicitacaoCarteira::firstOrFail();
        $this->assertSame($exAluno->name, $solicitacao->nome);
        $this->assertSame('52998224725', $solicitacao->cpf);

        $this->actingAs($this->usuario(papel: 'sru'))->patch("/carteiras/{$solicitacao->id}/analise", ['status' => 'aprovada', 'numero' => '123456']);

        $carteira = Carteira::firstOrFail();
        $this->assertNull($carteira->user_id);
        $this->assertSame('123456', $carteira->numero);
        $this->assertSame(today()->addYear()->toDateString(), $carteira->validade_fim->toDateString());
    }

    public function test_pendencia_permite_reenvio_pelo_titular(): void
    {
        $titular = $this->servidor();
        $this->solicitarConjuge($titular);
        $solicitacao = SolicitacaoCarteira::firstOrFail();
        $anterior = $solicitacao->documentos()->where('tipo', 'rg')->value('arquivo');

        $this->actingAs($titular)->post("/minhas-carteiras/{$solicitacao->id}/documentos", ['documentos' => $this->arquivos(['rg'])])->assertForbidden();

        $this->actingAs($this->usuario(papel: 'sru'))->patch("/carteiras/{$solicitacao->id}/analise", ['status' => 'pendente', 'observacao' => 'RG ilegível']);
        $this->actingAs($this->usuario())->post("/minhas-carteiras/{$solicitacao->id}/documentos", ['documentos' => $this->arquivos(['rg'])])->assertForbidden();
        $this->actingAs($titular)->post("/minhas-carteiras/{$solicitacao->id}/documentos", ['documentos' => $this->arquivos(['rg'])])->assertSessionHasNoErrors();

        $this->assertSame('em_analise', $solicitacao->fresh()->status);
        Storage::disk('local')->assertMissing($anterior);
        $this->assertSame(5, $solicitacao->documentos()->count());
    }

    public function test_ciclo_de_status_respeita_as_transicoes(): void
    {
        $titular = $this->servidor();
        $this->solicitarConjuge($titular);
        $solicitacao = SolicitacaoCarteira::firstOrFail();
        $sru = $this->usuario(papel: 'sru');
        $url = "/carteiras/{$solicitacao->id}/analise";

        // não pode pular etapas
        $this->actingAs($sru)->patch($url, ['status' => 'ativa'])->assertSessionHasErrors('status');
        $this->actingAs($sru)->patch($url, ['status' => 'em_confeccao'])->assertSessionHasErrors('status');

        $this->actingAs($sru)->patch($url, ['status' => 'aprovada', 'numero' => '90631234'])->assertSessionHasNoErrors();
        $carteira = $solicitacao->fresh()->carteira;
        $this->assertSame('aprovada', $carteira->fresh()->status);
        $this->assertFalse($carteira->fresh()->vigente());

        foreach (['aguardando_pagamento', 'em_confeccao', 'disponivel', 'ativa'] as $status) {
            $this->actingAs($sru)->patch("/carteiras/emitidas/{$carteira->id}/status", ['status' => $status])->assertSessionHasNoErrors();
            $this->assertSame($status, $carteira->fresh()->status);
        }

        $this->assertTrue($carteira->fresh()->vigente());
        $this->actingAs($sru)->patch("/carteiras/emitidas/{$carteira->id}/status", ['status' => 'inativa'])->assertSessionHasNoErrors();
        $this->assertFalse($carteira->fresh()->vigente());
        $this->actingAs($sru)->patch("/carteiras/emitidas/{$carteira->id}/status", ['status' => 'ativa'])->assertSessionHasErrors('status');
    }

    public function test_beneficio_so_apos_pagamento(): void
    {
        $titular = $this->servidor();
        $this->solicitarConjuge($titular);
        $solicitacao = SolicitacaoCarteira::firstOrFail();
        $dependente = $this->usuario(['cpf' => '52998224725']);
        $sru = $this->usuario(papel: 'sru');
        $url = "/carteiras/{$solicitacao->id}/analise";

        $this->actingAs($sru)->patch($url, ['status' => 'aprovada', 'numero' => '90631234']);
        $carteira = $solicitacao->fresh()->carteira;

        // aprovada sem pagamento confirmado: a SRU ainda pode vincular, mas o benefício não é liberado
        $this->assertNull($carteira->user_id);
        $this->actingAs($dependente)->post("/usuarios/{$dependente->id}/carteiras/vincular", ['carteira_id' => $carteira->id])->assertForbidden();
        $this->actingAs($sru)->post("/usuarios/{$dependente->id}/carteiras/vincular", ['carteira_id' => $carteira->id])->assertSessionHasNoErrors();
        $this->assertFalse($dependente->fresh()->perfilOuNovo()->numeroCepeuspVigente());

        $this->actingAs($sru)->patch("/carteiras/emitidas/{$carteira->id}/status", ['status' => 'aguardando_pagamento']);
        $this->assertFalse($dependente->fresh()->perfilOuNovo()->numeroCepeuspVigente());

        // pagamento confirmado (em confecção): benefício liberado
        $this->actingAs($sru)->patch("/carteiras/emitidas/{$carteira->id}/status", ['status' => 'em_confeccao']);
        $this->assertTrue($dependente->fresh()->perfilOuNovo()->numeroCepeuspVigente());
        $this->assertTrue(app(InscricaoService::class)->elegivel('tax_child', $dependente->fresh()));
        $this->actingAs($dependente)->get('/minhas-carteiras')->assertDontSee("renovar={$carteira->id}");
        $this->actingAs($dependente)->get("/minhas-carteiras/create?renovar={$carteira->id}")->assertNotFound();
    }

    public function test_renovacao_atualiza_mesma_carteira_sem_alterar_via(): void
    {
        $titular = $this->servidor();
        $this->solicitarConjuge($titular);
        $solicitacao = SolicitacaoCarteira::firstOrFail();
        $sru = $this->usuario(papel: 'sru');
        $url = "/carteiras/{$solicitacao->id}/analise";

        $this->actingAs($sru)->patch($url, ['status' => 'aprovada', 'numero' => '90631234', 'via' => 1]);
        $carteira = $solicitacao->fresh()->carteira;
        $this->assertSame(1, $carteira->fresh()->via);
        $carteira->update(['status' => 'ativa']);

        $this->actingAs($titular)->get('/minhas-carteiras')->assertOk()->assertSee("renovar={$carteira->id}");
        $this->actingAs($titular)->get("/minhas-carteiras/create?renovar={$carteira->id}")->assertOk();
        $this->actingAs($titular)->post('/minhas-carteiras', [
            'carteira_id' => $carteira->id,
            'categoria' => 'dependente_servidor',
            'nome' => 'Maria da Silva',
            'cpf' => '529.982.247-25',
            'data_nascimento' => '10/05/1985',
            'parentesco' => 'conjuge',
            'documentos' => $this->arquivos(['holerite', 'carteira_usp', 'foto', 'certidao_casamento', 'rg']),
        ])->assertSessionHasNoErrors();
        $renovacao = SolicitacaoCarteira::where('carteira_id', $carteira->id)->latest('id')->firstOrFail();
        $this->actingAs($sru)->patch("/carteiras/{$renovacao->id}/analise", ['status' => 'aprovada', 'numero' => '90631234'])->assertSessionHasNoErrors();
        $carteira->refresh();
        $this->assertSame(1, $carteira->via);
        $this->assertSame('90631234', $carteira->numero);
        $this->assertSame('aprovada', $carteira->status);

        $this->assertSame($carteira->id, $renovacao->fresh()->carteira_id);
        $this->assertSame(1, Carteira::count());
        $this->actingAs($titular)->get('/minhas-carteiras')->assertOk()->assertSee('90631234');
    }

    public function test_sru_cria_vincula_e_desvincula_carteira_no_perfil(): void
    {
        $sru = $this->usuario(papel: 'sru');
        $usuario = $this->usuario(['cpf' => '52998224725']);

        $this->actingAs($usuario)->post("/usuarios/{$usuario->id}/carteiras", [
            'numero' => '90631234', 'categoria' => 'dependente_servidor', 'parentesco' => 'conjuge', 'status' => 'ativa',
            'via' => 1, 'validade_inicio' => today()->toDateString(), 'validade_fim' => today()->addYear()->toDateString(),
        ])->assertForbidden();

        $this->actingAs($sru)->get("/usuarios/{$usuario->id}/perfil")->assertOk()->assertSee('Criar e vincular');
        $this->actingAs($sru)->post("/usuarios/{$usuario->id}/carteiras", [
            'numero' => '90631234', 'categoria' => 'dependente_servidor', 'parentesco' => 'conjuge', 'status' => 'ativa',
            'via' => 1, 'validade_inicio' => today()->toDateString(), 'validade_fim' => today()->addYear()->toDateString(),
        ])->assertRedirect("/usuarios/{$usuario->id}/perfil");

        $carteira = Carteira::where('numero', '90631234')->firstOrFail();
        $this->assertSame($usuario->id, $carteira->user_id);
        $this->assertSame('90631234', $usuario->fresh()->perfilOuNovo()->numeroCepeusp());
        $this->assertSame(0, SolicitacaoCarteira::count());
        $this->actingAs($usuario)->get('/minhas-carteiras')->assertDontSee("renovar={$carteira->id}");
        $this->actingAs($usuario)->get("/minhas-carteiras/create?renovar={$carteira->id}")->assertNotFound();

        $this->actingAs($sru)->delete("/usuarios/{$usuario->id}/carteiras/{$carteira->id}")
            ->assertRedirect("/usuarios/{$usuario->id}/perfil");
        $this->assertNull($carteira->fresh()->user_id);
        $this->assertDatabaseHas('carteiras', ['id' => $carteira->id, 'numero' => '90631234']);

        $this->actingAs($sru)->post("/usuarios/{$usuario->id}/carteiras/vincular", ['carteira_id' => $carteira->id])
            ->assertRedirect("/usuarios/{$usuario->id}/perfil");
        $this->assertSame($usuario->id, $carteira->fresh()->user_id);

        $legada = Carteira::create([
            'numero' => '90631235', 'categoria' => 'dependente_servidor', 'status' => 'ativa',
            'via' => 1, 'nome' => 'Legado', 'validade_inicio' => today(), 'validade_fim' => today()->addYear(),
        ]);
        $this->actingAs($sru)->post("/usuarios/{$usuario->id}/carteiras/vincular", ['carteira_id' => $legada->id])
            ->assertRedirect("/usuarios/{$usuario->id}/perfil");
        $this->assertSame($usuario->id, $legada->fresh()->user_id);
        $this->assertNull($legada->fresh()->solicitacoes()->first());

        $gestorCursos = $this->usuario(papel: 'gestor-cursos');
        $this->ativa($gestorCursos, ['user_id' => $gestorCursos->id, 'numero' => '90630011']);
        $html = $this->actingAs($gestorCursos)->get('/perfil')->assertOk()->assertSee('90630011')->getContent();
        $this->assertStringNotContainsString('Criar e vincular', $html);
        $this->actingAs($gestorCursos)->post("/usuarios/{$usuario->id}/carteiras/vincular", ['carteira_id' => $carteira->id])->assertForbidden();
    }

    public function test_carteira_vencida_nao_da_elegibilidade(): void
    {
        $titular = $this->servidor();
        $vencido = $this->usuario(['cpf' => '52998224725']);
        $this->ativa($titular, ['validade_fim' => now()->subDay()->toDateString(), 'user_id' => $vencido->id]);
        $vigente = $this->usuario();
        $this->ativa($titular, ['numero' => '90630005', 'user_id' => $vigente->id]);

        $inscricoes = app(InscricaoService::class);
        $this->assertFalse($inscricoes->elegivel('tax_child', $vencido->fresh()));
        $this->assertFalse($inscricoes->elegivel('tax_cepe', $vencido->fresh()));
        $this->assertTrue($inscricoes->elegivel('tax_child', $vigente->fresh()));
        $this->assertSame('vencida', Carteira::where('numero', '90631234')->first()->status_efetivo);
    }
}
