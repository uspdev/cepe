<?php

namespace Tests\Feature;

use App\Models\Carteira;
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
            'solicitante_id' => $titular->id, 'categoria' => 'dependente_servidor',
            'nome' => 'Maria da Silva', 'cpf' => '52998224725', 'data_nascimento' => '1985-05-10', 'parentesco' => 'conjuge',
            'status' => 'ativa', 'numero' => '90631234', 'via' => 1,
            'validade_inicio' => now()->toDateString(), 'validade_fim' => now()->addYear()->toDateString(),
        ]);
    }

    public function test_titular_servidor_solicita_carteira_do_conjuge(): void
    {
        $titular = $this->servidor();

        $this->solicitarConjuge($titular)->assertSessionHasNoErrors()->assertRedirect();

        $carteira = Carteira::with('documentos')->firstOrFail();
        $this->assertSame('em_analise', $carteira->status);
        $this->assertSame('52998224725', $carteira->cpf);
        $this->assertSame($titular->id, $carteira->solicitante_id);
        $this->assertEqualsCanonicalizing(['holerite', 'carteira_usp', 'foto', 'certidao_casamento', 'rg'], $carteira->documentos->pluck('tipo')->all());
        $carteira->documentos->each(fn ($d) => Storage::disk('local')->assertExists($d->arquivo));
    }

    public function test_documento_obrigatorio_do_parentesco_e_foto_em_jpg(): void
    {
        $titular = $this->servidor();
        $documentos = $this->arquivos(['holerite', 'carteira_usp', 'certidao_casamento']);
        $documentos['foto'] = UploadedFile::fake()->create('foto.pdf', 10, 'application/pdf');

        $this->solicitarConjuge($titular, ['documentos' => $documentos])
            ->assertSessionHasErrors(['documentos.rg', 'documentos.foto']);
        $this->assertSame(0, Carteira::count());
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

        $this->assertSame(0, Carteira::count());
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
        $carteira = Carteira::with('documentos')->firstOrFail();
        $documento = $carteira->documentos->first();

        $this->actingAs($this->usuario())->get('/carteiras')->assertForbidden();
        $this->actingAs($this->usuario(papel: 'gestor-cursos'))->get("/carteiras/{$carteira->id}")->assertForbidden();
        $this->actingAs($this->usuario())->get("/minhas-carteiras/{$carteira->id}")->assertForbidden();
        $this->actingAs($this->usuario())->get("/carteiras/{$carteira->id}/documentos/{$documento->id}")->assertForbidden();

        $this->actingAs($titular)->get("/carteiras/{$carteira->id}/documentos/{$documento->id}")->assertOk();
        $sru = $this->usuario(papel: 'sru');
        $this->actingAs($sru)->get('/carteiras')->assertOk()->assertSee('Maria da Silva');
        $this->actingAs($sru)->get("/carteiras/{$carteira->id}")->assertOk();
        $this->actingAs($sru)->get("/carteiras/{$carteira->id}/documentos/{$documento->id}")->assertOk();
    }

    public function test_aprovacao_exige_numero_unico_com_prefixo_e_define_validade(): void
    {
        $titular = $this->servidor();
        $this->solicitarConjuge($titular);
        $carteira = Carteira::firstOrFail();
        $this->ativa($this->servidor(), ['numero' => '90639999']);
        $sru = $this->usuario(papel: 'sru');
        $url = "/carteiras/{$carteira->id}/analise";

        $this->actingAs($sru)->patch($url, ['status' => 'aprovada'])->assertSessionHasErrors('numero');
        $this->actingAs($sru)->patch($url, ['status' => 'aprovada', 'numero' => '91631234'])->assertSessionHasErrors('numero');
        $this->actingAs($sru)->patch($url, ['status' => 'aprovada', 'numero' => '90639999'])->assertSessionHasErrors('numero');
        $this->actingAs($sru)->patch($url, ['status' => 'reprovada'])->assertSessionHasErrors('observacao');
        $this->actingAs($this->usuario(papel: 'gestor-cursos'))->patch($url, ['status' => 'aprovada', 'numero' => '90631234'])->assertForbidden();

        $this->actingAs($sru)->patch($url, ['status' => 'aprovada', 'numero' => '90631234', 'via' => 1])->assertSessionHasNoErrors();

        $carteira->refresh();
        $this->assertSame('aprovada', $carteira->status);
        $this->assertSame('90631234', $carteira->numero);
        $this->assertSame(1, $carteira->via);
        $this->assertSame(today()->toDateString(), $carteira->validade_inicio->toDateString());
        $this->assertSame(today()->addYear()->toDateString(), $carteira->validade_fim->toDateString());
        $this->assertSame($sru->id, $carteira->analisado_por);
        $this->assertNull($carteira->user_id);
    }

    public function test_aprovacao_de_categoria_propria_vincula_ao_solicitante(): void
    {
        $exAluno = $this->usuario(['cpf' => '52998224725', 'data_nascimento' => '1980-01-01']);
        $this->actingAs($exAluno)->post('/minhas-carteiras', [
            'categoria' => 'ex_aluno',
            'documentos' => $this->arquivos(['diploma', 'rg_cnh', 'foto']),
        ])->assertSessionHasNoErrors();
        $carteira = Carteira::firstOrFail();
        $this->assertSame($exAluno->name, $carteira->nome);
        $this->assertSame('52998224725', $carteira->cpf);

        $this->actingAs($this->usuario(papel: 'sru'))->patch("/carteiras/{$carteira->id}/analise", ['status' => 'aprovada', 'numero' => '123456']);

        $carteira->refresh();
        $this->assertSame($exAluno->id, $carteira->user_id);
        $this->assertSame('123456', $carteira->numero);
        $this->assertSame(today()->addYear()->toDateString(), $carteira->validade_fim->toDateString());
    }

    public function test_pendencia_permite_reenvio_pelo_titular(): void
    {
        $titular = $this->servidor();
        $this->solicitarConjuge($titular);
        $carteira = Carteira::firstOrFail();
        $anterior = $carteira->documentos()->where('tipo', 'rg')->value('arquivo');

        $this->actingAs($titular)->post("/minhas-carteiras/{$carteira->id}/documentos", ['documentos' => $this->arquivos(['rg'])])->assertForbidden();

        $this->actingAs($this->usuario(papel: 'sru'))->patch("/carteiras/{$carteira->id}/analise", ['status' => 'pendente', 'observacao' => 'RG ilegível']);
        $this->actingAs($this->usuario())->post("/minhas-carteiras/{$carteira->id}/documentos", ['documentos' => $this->arquivos(['rg'])])->assertForbidden();
        $this->actingAs($titular)->post("/minhas-carteiras/{$carteira->id}/documentos", ['documentos' => $this->arquivos(['rg'])])->assertSessionHasNoErrors();

        $this->assertSame('em_analise', $carteira->fresh()->status);
        Storage::disk('local')->assertMissing($anterior);
        $this->assertSame(5, $carteira->documentos()->count());
    }

    public function test_ciclo_de_status_respeita_as_transicoes(): void
    {
        $titular = $this->servidor();
        $this->solicitarConjuge($titular);
        $carteira = Carteira::firstOrFail();
        $sru = $this->usuario(papel: 'sru');
        $url = "/carteiras/{$carteira->id}/analise";

        // não pode pular etapas
        $this->actingAs($sru)->patch($url, ['status' => 'ativa'])->assertSessionHasErrors('status');
        $this->actingAs($sru)->patch($url, ['status' => 'em_confeccao'])->assertSessionHasErrors('status');

        $this->actingAs($sru)->patch($url, ['status' => 'aprovada', 'numero' => '90631234'])->assertSessionHasNoErrors();
        $this->assertSame('aprovada', $carteira->fresh()->status);
        $this->assertFalse($carteira->fresh()->vigente());

        foreach (['aguardando_pagamento', 'em_confeccao', 'disponivel', 'ativa'] as $status) {
            $this->actingAs($sru)->patch($url, ['status' => $status])->assertSessionHasNoErrors();
            $this->assertSame($status, $carteira->fresh()->status);
        }

        $this->assertTrue($carteira->fresh()->vigente());
        $this->actingAs($sru)->patch($url, ['status' => 'inativa'])->assertSessionHasNoErrors();
        $this->assertFalse($carteira->fresh()->vigente());
        $this->actingAs($sru)->patch($url, ['status' => 'ativa'])->assertSessionHasErrors('status');
    }

    public function test_beneficio_so_apos_pagamento(): void
    {
        $titular = $this->servidor();
        $this->solicitarConjuge($titular);
        $carteira = Carteira::firstOrFail();
        $dependente = $this->usuario(['cpf' => '52998224725']);
        $sru = $this->usuario(papel: 'sru');
        $url = "/carteiras/{$carteira->id}/analise";

        $this->actingAs($sru)->patch($url, ['status' => 'aprovada', 'numero' => '90631234']);
        $carteira->forceFill(['user_id' => $dependente->id])->save();

        // aprovada sem pagamento confirmado: dependente não é elegível nem vincula
        $this->assertFalse($dependente->fresh()->perfilOuNovo()->numeroCepeuspVigente());
        $this->actingAs($dependente)->post('/perfil/carteira', ['numero' => '90631234'])->assertSessionHasErrors('numero');

        $this->actingAs($sru)->patch($url, ['status' => 'aguardando_pagamento']);
        $this->assertFalse($dependente->fresh()->perfilOuNovo()->numeroCepeuspVigente());

        // pagamento confirmado (em confecção): benefício liberado
        $this->actingAs($sru)->patch($url, ['status' => 'em_confeccao']);
        $this->assertTrue($dependente->fresh()->perfilOuNovo()->numeroCepeuspVigente());
        $this->assertTrue(app(InscricaoService::class)->elegivel('tax_child', $dependente->fresh()));
    }

    public function test_numero_e_via_seguem_editaveis_apos_aprovacao(): void
    {
        $titular = $this->servidor();
        $this->solicitarConjuge($titular);
        $carteira = Carteira::firstOrFail();
        $sru = $this->usuario(papel: 'sru');
        $url = "/carteiras/{$carteira->id}/analise";

        $this->actingAs($sru)->patch($url, ['status' => 'aprovada', 'numero' => '90631234', 'via' => 1]);
        $this->assertSame(1, $carteira->fresh()->via);

        // segunda via: mesma ação, mantendo o status atual
        $this->actingAs($sru)->patch($url, ['status' => 'aprovada', 'numero' => '90631234', 'via' => 2])->assertSessionHasNoErrors();
        $carteira->refresh();
        $this->assertSame(2, $carteira->via);
        $this->assertSame('90631234', $carteira->numero);
        $this->assertSame('aprovada', $carteira->status);

        $this->actingAs($titular)->get('/minhas-carteiras')->assertOk()->assertSee('2ª via');
    }

    public function test_dependente_vincula_numero_e_fica_elegivel(): void
    {
        $titular = $this->servidor();
        $carteira = $this->ativa($titular);
        $dependente = $this->usuario(['cpf' => '52998224725']);

        $this->actingAs($dependente)->post('/perfil/carteira', ['numero' => '90631234'])->assertSessionHasNoErrors()->assertRedirect('/perfil');

        $this->assertSame($dependente->id, $carteira->fresh()->user_id);
        $this->assertSame('90631234', $dependente->fresh()->perfilOuNovo()->numeroCepeusp());
        $this->assertSame('dependente', $dependente->fresh()->perfilOuNovo()->categoriaCepeusp());
        $this->assertTrue(app(InscricaoService::class)->elegivel('tax_child', $dependente->fresh()));
    }

    public function test_vinculo_recusa_dados_divergentes_vencida_ou_ja_vinculada(): void
    {
        $titular = $this->servidor();
        $this->ativa($titular);
        $this->ativa($titular, ['numero' => '90630001', 'validade_fim' => now()->subDay()->toDateString()]);
        $this->ativa($titular, ['numero' => '90630002', 'user_id' => $this->usuario()->id]);

        $outro = $this->usuario(['cpf' => '11144477735']);
        $this->actingAs($outro)->post('/perfil/carteira', ['numero' => '90631234'])->assertSessionHasErrors('numero');
        $this->actingAs($outro)->post('/perfil/carteira', ['numero' => '99999999'])->assertSessionHasErrors('numero');

        $dono = $this->usuario(['cpf' => '52998224725']);
        $this->actingAs($dono)->post('/perfil/carteira', ['numero' => '90630001'])->assertSessionHasErrors('numero');
        $this->actingAs($dono)->post('/perfil/carteira', ['numero' => '90630002'])->assertSessionHasErrors('numero');

        $this->assertFalse($outro->fresh()->perfilOuNovo()->numeroCepeuspVigente());
        $this->assertFalse($dono->fresh()->perfilOuNovo()->numeroCepeuspVigente());
    }

    public function test_vinculo_sem_cpf_confere_nome_e_nascimento(): void
    {
        $this->ativa($this->servidor(), ['cpf' => null]);
        $dependente = $this->usuario(['sem_cpf' => true, 'data_nascimento' => '1985-05-10'], user: ['name' => 'MARIA DA  SILVA']);

        $this->actingAs($dependente)->post('/perfil/carteira', ['numero' => '90631234'])->assertSessionHasNoErrors();
        $this->assertTrue($dependente->fresh()->perfilOuNovo()->numeroCepeuspVigente());
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
