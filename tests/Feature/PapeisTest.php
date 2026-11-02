<?php

namespace Tests\Feature;

use App\Models\Atestado;
use App\Models\Atividade;
use App\Models\Matricula;
use App\Models\Oferecimento;
use App\Models\Turma;
use App\Models\User;
use App\Support\Papeis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PapeisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::define('admin', fn ($u) => $u->email === 'admin@teste.com');
        Gate::define('user', fn () => true);
        Papeis::sincronizar();
    }

    private function usuario(?string $papel = null, array $extra = []): User
    {
        $user = User::factory()->create($extra);
        $papel && $user->assignRole($papel);

        return $user;
    }

    private function admin(): User
    {
        return $this->usuario(null, ['email' => 'admin@teste.com']);
    }

    private function atividade(string $tipo): Atividade
    {
        return Atividade::create(['nome' => "Atividade {$tipo}", 'descricao' => 'd', 'tipo' => $tipo, 'status' => 'ativo']);
    }

    private function turma(string $tipo): Turma
    {
        $oferecimento = Oferecimento::create(['atividade_id' => $this->atividade($tipo)->id, 'periodo' => '01-2026', 'pagamento' => 'pix']);

        return Turma::create(['oferecimento_id' => $oferecimento->id]);
    }

    private function documento(string $tipo, ?User $dono = null): Atestado
    {
        return Atestado::create([
            'user_id' => ($dono ?? $this->usuario())->id,
            'tipo' => $tipo,
            'arquivo' => 'x.pdf',
            'emitido_em' => now()->toDateString(),
            'status' => 'em_analise',
        ]);
    }

    public function test_sincronizacao_cria_papeis_e_e_idempotente(): void
    {
        Papeis::sincronizar();

        $this->assertSame(array_keys(config('cepe.papeis')), Role::where('guard_name', 'web')->orderBy('id')->pluck('name')->all());
        $this->assertSame(count(config('cepe.permissoes')), Permission::where('guard_name', 'web')->count());
        $this->assertEqualsCanonicalizing(
            config('cepe.papeis.gestor-cursos'),
            Role::findByName('gestor-cursos', 'web')->permissions->pluck('name')->all()
        );
        $this->assertSame(0, Role::findByName('professor', 'web')->permissions->count());
    }

    public function test_sincronizacao_funciona_em_seeder_sem_eventos_de_modelo(): void
    {
        Role::query()->delete();
        Permission::query()->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // o DatabaseSeeder usa WithoutModelEvents, que impede o Spatie de limpar o próprio cache
        Model::withoutEvents(fn () => Papeis::sincronizar());

        $this->assertSame(count(config('cepe.papeis')), Role::count());
        $this->assertSame(count(config('cepe.permissoes')), Permission::count());
        $this->assertEqualsCanonicalizing(
            config('cepe.papeis.sru'),
            Role::findByName('sru', 'web')->permissions->pluck('name')->all()
        );
    }

    public function test_gestor_cursos_so_gerencia_atividades_de_cursos(): void
    {
        $gestor = $this->usuario('gestor-cursos');
        $curso = $this->atividade('semestral');
        $evento = $this->atividade('voltinhausp');

        $this->actingAs($gestor)->get("/atividades/{$curso->id}")->assertOk();
        $this->actingAs($gestor)->get("/atividades/{$evento->id}")->assertForbidden();
        $this->actingAs($gestor)->patch("/atividades/{$evento->id}", ['nome' => 'x', 'descricao' => 'd', 'tipo' => 'eventos', 'status' => 'ativo'])->assertForbidden();
        $this->actingAs($gestor)->delete("/atividades/{$evento->id}")->assertForbidden();

        $lista = $this->actingAs($gestor)->get('/atividades')->assertOk()->viewData('atividades');
        $this->assertSame([$curso->id], $lista->pluck('id')->all());
    }

    public function test_gestor_nao_cria_atividade_de_outro_grupo(): void
    {
        $gestor = $this->usuario('gestor-cursos');
        $dados = ['nome' => 'Nova', 'descricao' => 'd', 'status' => 'ativo'];

        $this->actingAs($gestor)->post('/atividades', $dados + ['tipo' => 'eventos'])->assertSessionHasErrors('tipo');
        $this->assertSame(0, Atividade::count());

        $this->actingAs($gestor)->post('/atividades', $dados + ['tipo' => 'mensal'])->assertSessionHasNoErrors();
        $this->assertSame(1, Atividade::count());
    }

    public function test_gestor_eventos_inclui_voltausp_e_voltinhausp(): void
    {
        $gestor = $this->usuario('gestor-eventos');

        $this->assertEqualsCanonicalizing(['eventos', 'voltausp', 'voltinhausp'], $gestor->tiposPermitidos());
        $this->actingAs($gestor)->get('/atividades/'.$this->atividade('voltinhausp')->id)->assertOk();
        $this->actingAs($gestor)->get('/atividades/'.$this->atividade('trimestral')->id)->assertForbidden();
    }

    public function test_oferecimentos_e_turmas_seguem_o_grupo_da_atividade(): void
    {
        $gestor = $this->usuario('gestor-cursos');
        $turmaCurso = $this->turma('semestral');
        $turmaEvento = $this->turma('eventos');

        $this->actingAs($gestor)->get("/turmas/{$turmaCurso->id}/edit")->assertOk();
        $this->actingAs($gestor)->get("/turmas/{$turmaEvento->id}/edit")->assertForbidden();
        $this->actingAs($gestor)->delete("/turmas/{$turmaEvento->id}")->assertForbidden();
        $this->actingAs($gestor)->post('/turmas', ['oferecimento_id' => $turmaEvento->oferecimento_id])->assertForbidden();
        $this->actingAs($gestor)->patch("/turmas/{$turmaCurso->id}", ['oferecimento_id' => $turmaEvento->oferecimento_id])->assertForbidden();
        $this->actingAs($gestor)->get("/oferecimentos/{$turmaEvento->oferecimento->atividade_id}/create")->assertForbidden();
        $this->actingAs($gestor)->get("/oferecimentos/{$turmaCurso->oferecimento->atividade_id}/create")->assertOk();

        $this->assertSame(2, Turma::count());
    }

    public function test_usuario_sem_papel_e_professor_nao_acessam_a_gestao(): void
    {
        $atividade = $this->atividade('semestral');
        $this->documento('atestado_medico');

        foreach ([$this->usuario(), $this->usuario('professor')] as $user) {
            $this->actingAs($user)->get('/atividades')->assertForbidden();
            $this->actingAs($user)->get("/atividades/{$atividade->id}")->assertForbidden();
            $this->actingAs($user)->get('/atestados')->assertForbidden();
        }
    }

    public function test_atestados_sao_separados_por_tipo_entre_sru_e_avaliador(): void
    {
        $clinico = $this->documento('atestado_medico');
        $parq = $this->documento('parq');
        $dermato = $this->documento('exame_dermatologico');
        $sru = $this->usuario('sru');
        $avaliador = $this->usuario('avaliador-dermatologico');

        $this->assertEqualsCanonicalizing([$clinico->id, $parq->id], $this->actingAs($sru)->get('/atestados?status=todos')->viewData('atestados')->pluck('id')->all());
        $this->assertSame([$dermato->id], $this->actingAs($avaliador)->get('/atestados?status=todos')->viewData('atestados')->pluck('id')->all());

        $this->actingAs($sru)->get("/atestados/{$clinico->id}")->assertOk();
        $this->actingAs($sru)->get("/atestados/{$dermato->id}")->assertForbidden();
        $this->actingAs($sru)->patch("/atestados/{$dermato->id}/analise", ['status' => 'aprovado'])->assertForbidden();
        $this->actingAs($avaliador)->get("/atestados/{$clinico->id}")->assertForbidden();
        $this->actingAs($avaliador)->patch("/atestados/{$dermato->id}/analise", ['status' => 'aprovado'])->assertRedirect();
        $this->assertSame('aprovado', $dermato->fresh()->status);

        $this->actingAs($avaliador)->delete("/atestados/{$clinico->id}")->assertForbidden();
        $this->actingAs($sru)->patch("/atestados/{$parq->id}/analise", ['status' => 'aprovado'])->assertRedirect();
    }

    public function test_dono_continua_vendo_o_proprio_arquivo(): void
    {
        $dono = $this->usuario();
        $doc = $this->documento('exame_dermatologico', $dono);

        $this->assertTrue(Gate::forUser($dono)->allows('view', $doc));
        $this->assertFalse(Gate::forUser($this->usuario())->allows('view', $doc));
        $this->assertFalse(Gate::forUser($this->usuario('sru'))->allows('view', $doc));
        $this->assertTrue(Gate::forUser($this->usuario('avaliador-dermatologico'))->allows('view', $doc));
    }

    public function test_campos_restritos_do_perfil_so_para_quem_gerencia_perfis(): void
    {
        $this->assertTrue(Gate::forUser($this->usuario('sru'))->allows('perfis.gerenciar'));

        foreach (['gestor-cursos', 'gestor-eventos', 'avaliador-dermatologico', 'professor', null] as $papel) {
            $this->assertFalse(Gate::forUser($this->usuario($papel))->allows('perfis.gerenciar'), (string) $papel);
        }
    }

    public function test_sru_edita_perfil_de_outro_usuario_mas_gestor_nao(): void
    {
        $alvo = $this->usuario();

        $this->actingAs($this->usuario('sru'))->get("/usuarios/{$alvo->id}/perfil")->assertOk();
        $this->actingAs($this->usuario('gestor-cursos'))->get("/usuarios/{$alvo->id}/perfil")->assertForbidden();
    }

    public function test_cancelamento_de_matricula_de_terceiros_depende_do_grupo(): void
    {
        $turma = $this->turma('semestral');
        $aluno = $this->usuario();
        $nova = fn () => Matricula::create([
            'user_id' => $aluno->id, 'perfil' => 'tax_in', 'turma_id' => $turma->id,
            'periodo_inscricao' => 1, 'periodo_oferecimento' => '01-2026', 'status' => Matricula::MATRICULADA,
        ]);

        $this->actingAs($this->usuario())->patch("/minhas-inscricoes/{$nova()->id}/cancelar")->assertForbidden();
        $this->actingAs($this->usuario('gestor-eventos'))->patch("/minhas-inscricoes/{$nova()->id}/cancelar")->assertForbidden();
        $this->actingAs($this->usuario('professor'))->patch("/minhas-inscricoes/{$nova()->id}/cancelar")->assertForbidden();

        $this->actingAs($this->usuario('gestor-cursos'))->patch("/minhas-inscricoes/{$nova()->id}/cancelar")->assertRedirect();
        $this->actingAs($this->usuario('sru'))->patch("/minhas-inscricoes/{$nova()->id}/cancelar")->assertRedirect();
        $this->actingAs($aluno)->patch("/minhas-inscricoes/{$nova()->id}/cancelar")->assertRedirect();
    }

    public function test_gate_aluno_e_quem_nao_tem_papeis(): void
    {
        $this->assertTrue(Gate::forUser($this->usuario())->allows('aluno'));

        foreach (array_keys(config('cepe.papeis')) as $papel) {
            $this->assertFalse(Gate::forUser($this->usuario($papel))->allows('aluno'), $papel);
        }
    }

    public function test_admin_passa_em_todas_as_permissions_e_grupos(): void
    {
        $admin = $this->admin();

        foreach (config('cepe.permissoes') as $permissao) {
            $this->assertTrue(Gate::forUser($admin)->allows($permissao), $permissao);
        }
        $this->assertEqualsCanonicalizing(['mensal', 'bimestral', 'trimestral', 'semestral', 'eventos', 'voltausp', 'voltinhausp'], $admin->tiposPermitidos());
        $this->assertEqualsCanonicalizing(array_keys(config('cepe.atestados_permissoes')), $admin->tiposAtestadoAnalisaveis());
        $this->actingAs($admin)->get('/atividades/'.$this->atividade('eventos')->id)->assertOk();
    }

    public function test_gates_de_menu(): void
    {
        $this->assertTrue(Gate::forUser($this->usuario('gestor-cursos'))->allows('gerenciar-catalogo'));
        $this->assertFalse(Gate::forUser($this->usuario('sru'))->allows('gerenciar-catalogo'));
        $this->assertTrue(Gate::forUser($this->usuario('sru'))->allows('analisar-atestados'));
        $this->assertTrue(Gate::forUser($this->usuario('avaliador-dermatologico'))->allows('analisar-atestados'));
        $this->assertFalse(Gate::forUser($this->usuario('professor'))->allows('analisar-atestados'));
        $this->assertFalse(Gate::forUser($this->usuario())->allows('gerenciar-catalogo'));
    }
}
