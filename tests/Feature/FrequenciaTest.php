<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\Chamada;
use App\Models\Matricula;
use App\Models\Oferecimento;
use App\Models\Presenca;
use App\Models\Turma;
use App\Models\User;
use App\Support\Papeis;
use Database\Seeders\ProfessorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class FrequenciaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::define('admin', fn ($u) => $u->email === 'admin@teste.com');
        Papeis::sincronizar();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function usuario(?string $papel = null): User
    {
        $user = User::factory()->create();
        $papel && $user->assignRole($papel);

        return $user;
    }

    private function turma(string $tipo, ?User $professor = null, array $extra = []): Turma
    {
        $atividade = Atividade::create(['nome' => "Atividade {$tipo}", 'descricao' => 'd', 'tipo' => $tipo, 'status' => 'ativo']);
        $oferecimento = Oferecimento::create(['atividade_id' => $atividade->id, 'periodo' => '01-2026', 'pagamento' => 'pix']);

        return Turma::create(array_merge([
            'oferecimento_id' => $oferecimento->id,
            'professor_id' => $professor?->id,
            'dias_semana' => ['terca', 'quinta'],
            'horario_inicio' => '08:00',
            'horario_fim' => '12:00',
        ], $extra));
    }

    private function matricula(Turma $turma, User $aluno, string $status = Matricula::MATRICULADA): Matricula
    {
        return Matricula::create([
            'user_id' => $aluno->id, 'perfil' => 'tax_in', 'turma_id' => $turma->id,
            'periodo_inscricao' => 1, 'periodo_oferecimento' => '01-2026', 'status' => $status,
        ]);
    }

    public function test_professor_registra_presenca_no_dia_e_horario_do_curso_sem_abrir_chamada(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-27 09:00:00', 'America/Sao_Paulo'));
        $professor = $this->usuario('professor');
        $turma = $this->turma('semestral', $professor);
        $matricula = $this->matricula($turma, $this->usuario());

        $this->actingAs($professor)->get("/turmas/{$turma->id}/frequencia/hoje")->assertRedirect();
        $chamada = Chamada::where('turma_id', $turma->id)->firstOrFail();

        $this->actingAs($professor)
            ->patch("/turmas/{$turma->id}/frequencia/{$chamada->id}", ['presencas' => [$matricula->id => Presenca::FALTA]])
            ->assertRedirect();

        $presenca = Presenca::where('matricula_id', $matricula->id)->firstOrFail();
        $this->assertSame(Presenca::FALTA, $presenca->status);
        $this->assertSame(Presenca::PROFESSOR, $presenca->origem);
    }

    public function test_professor_e_gestor_incluem_presenca_a_qualquer_momento(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-28 13:00:00', 'America/Sao_Paulo'));
        $professor = $this->usuario('professor');
        $turma = $this->turma('semestral', $professor);
        $matricula = $this->matricula($turma, $this->usuario());
        $chamada = $turma->chamadas()->create(['data' => '2026-10-27']);

        $this->actingAs($professor)
            ->patch("/turmas/{$turma->id}/frequencia/{$chamada->id}", ['presencas' => [$matricula->id => Presenca::PRESENTE]])
            ->assertSessionHas('alert-success');
        $this->actingAs($this->usuario('gestor-cursos'))->get("/turmas/{$turma->id}/frequencia/hoje")->assertRedirect();

        $this->assertSame(Presenca::PRESENTE, Presenca::where('matricula_id', $matricula->id)->value('status'));
        $this->assertSame(Presenca::PROFESSOR, Presenca::where('matricula_id', $matricula->id)->value('origem'));
    }

    public function test_frequencia_respeita_vinculo_e_grupo(): void
    {
        $professor = $this->usuario('professor');
        $curso = $this->turma('semestral', $professor);
        $evento = $this->turma('voltinhausp');

        $this->actingAs($professor)->get("/turmas/{$curso->id}/frequencia")->assertOk();
        $this->actingAs($this->usuario('professor'))->get("/turmas/{$curso->id}/frequencia")->assertForbidden();
        $this->actingAs($this->usuario('gestor-cursos'))->get("/turmas/{$curso->id}/frequencia")->assertOk();
        $this->actingAs($this->usuario('sru'))->get("/turmas/{$curso->id}/frequencia")->assertOk();
        $this->actingAs($this->usuario('sru'))->get("/turmas/{$evento->id}/frequencia")->assertForbidden();
        $this->actingAs($this->usuario('gestor-eventos'))->get("/turmas/{$curso->id}/frequencia")->assertForbidden();
        $this->actingAs($this->usuario())->get("/turmas/{$curso->id}/frequencia")->assertForbidden();
    }

    public function test_checkin_dentro_da_janela_registra_presenca_do_aluno(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-27 11:30:00', 'America/Sao_Paulo'));
        $turma = $this->turma('semestral');
        $aluno = $this->usuario();
        $matricula = $this->matricula($turma, $aluno);

        $this->actingAs($aluno)->post("/minhas-inscricoes/{$matricula->id}/presenca")->assertSessionHas('alert-success');

        $presenca = Presenca::where('matricula_id', $matricula->id)->firstOrFail();
        $this->assertSame(Presenca::PRESENTE, $presenca->status);
        $this->assertSame(Presenca::ALUNO, $presenca->origem);
    }

    public function test_checkin_fora_do_horario_ou_do_dia_nao_registra(): void
    {
        $turma = $this->turma('semestral');
        $aluno = $this->usuario();
        $matricula = $this->matricula($turma, $aluno);

        Carbon::setTestNow(Carbon::parse('2026-10-27 13:01:00', 'America/Sao_Paulo'));
        $this->actingAs($aluno)->post("/minhas-inscricoes/{$matricula->id}/presenca")->assertSessionHas('alert-danger');

        Carbon::setTestNow(Carbon::parse('2026-10-27 06:59:00', 'America/Sao_Paulo'));
        $this->actingAs($aluno)->post("/minhas-inscricoes/{$matricula->id}/presenca")->assertSessionHas('alert-danger');

        Carbon::setTestNow(Carbon::parse('2026-10-26 10:00:00', 'America/Sao_Paulo'));
        $this->actingAs($aluno)->post("/minhas-inscricoes/{$matricula->id}/presenca")->assertSessionHas('alert-danger');

        $this->assertSame(0, Presenca::count());
    }

    public function test_checkin_aceita_tolerancia_antes_e_depois_da_aula(): void
    {
        $turma = $this->turma('semestral');
        $aluno = $this->usuario();
        $matricula = $this->matricula($turma, $aluno);

        Carbon::setTestNow(Carbon::parse('2026-10-27 07:00:00', 'America/Sao_Paulo'));
        $this->actingAs($aluno)->post("/minhas-inscricoes/{$matricula->id}/presenca")->assertSessionHas('alert-success');

        Carbon::setTestNow(Carbon::parse('2026-10-29 13:00:00', 'America/Sao_Paulo'));
        $this->actingAs($aluno)->post("/minhas-inscricoes/{$matricula->id}/presenca")->assertSessionHas('alert-success');

        $this->assertSame(2, Presenca::count());
    }

    public function test_aluno_registra_e_visualiza_sua_frequencia(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-27 09:00:00', 'America/Sao_Paulo'));
        $aluno = $this->usuario();
        $matricula = $this->matricula($this->turma('semestral'), $aluno);

        $this->actingAs($aluno)->get('/minha-frequencia')->assertOk()->assertSee('Registrar presença de hoje');
        $this->actingAs($aluno)->post("/minhas-inscricoes/{$matricula->id}/presenca");
        $this->actingAs($aluno)->get('/minha-frequencia')->assertOk()->assertSee('Presente')->assertDontSee('Registrar presença de hoje');
    }

    public function test_checkin_duplicado_nao_cria_outra_presenca(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-27 09:00:00', 'America/Sao_Paulo'));
        $aluno = $this->usuario();
        $matricula = $this->matricula($this->turma('semestral'), $aluno);

        $this->actingAs($aluno)->post("/minhas-inscricoes/{$matricula->id}/presenca")->assertSessionHas('alert-success');
        $this->actingAs($aluno)->post("/minhas-inscricoes/{$matricula->id}/presenca")->assertSessionHas('alert-info');

        $this->assertSame(1, Presenca::count());
        $this->assertSame(1, Chamada::count());
    }

    public function test_checkin_de_outro_aluno_ou_matricula_cancelada_e_negado(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-27 09:00:00', 'America/Sao_Paulo'));
        $turma = $this->turma('semestral');
        $aluno = $this->usuario();
        $ativa = $this->matricula($turma, $aluno);
        $cancelada = $this->matricula($turma, $aluno, Matricula::CANCELADA);

        $this->actingAs($this->usuario())->post("/minhas-inscricoes/{$ativa->id}/presenca")->assertForbidden();
        $this->actingAs($aluno)->post("/minhas-inscricoes/{$cancelada->id}/presenca")->assertForbidden();
    }

    public function test_professor_e_gestor_corrigem_presenca_fora_do_horario(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-28 09:00:00', 'America/Sao_Paulo'));
        $professor = $this->usuario('professor');
        $turma = $this->turma('semestral', $professor);
        $matricula = $this->matricula($turma, $this->usuario());
        $chamada = $turma->chamadas()->create(['data' => '2026-10-27']);
        Presenca::create(['chamada_id' => $chamada->id, 'matricula_id' => $matricula->id, 'status' => Presenca::FALTA, 'origem' => Presenca::PROFESSOR]);

        $this->actingAs($professor)
            ->patch("/turmas/{$turma->id}/frequencia/{$chamada->id}", ['presencas' => [$matricula->id => Presenca::JUSTIFICADA]])
            ->assertRedirect();
        $this->assertSame(Presenca::JUSTIFICADA, Presenca::where('matricula_id', $matricula->id)->value('status'));

        $this->actingAs($this->usuario('gestor-cursos'))
            ->patch("/turmas/{$turma->id}/frequencia/{$chamada->id}", ['presencas' => [$matricula->id => Presenca::PRESENTE]])
            ->assertRedirect();
        $this->assertSame(Presenca::PRESENTE, Presenca::where('matricula_id', $matricula->id)->value('status'));
    }

    public function test_professor_corrige_data_da_chamada_sem_repetir_datas(): void
    {
        $professor = $this->usuario('professor');
        $turma = $this->turma('semestral', $professor);
        $chamada = $turma->chamadas()->create(['data' => '2026-10-27']);
        $turma->chamadas()->create(['data' => '2026-10-29']);

        $this->actingAs($professor)->patch("/turmas/{$turma->id}/frequencia/{$chamada->id}/data", ['data' => '2026-10-29'])
            ->assertSessionHas('alert-danger');
        $this->actingAs($professor)->patch("/turmas/{$turma->id}/frequencia/{$chamada->id}/data", ['data' => '2026-10-28'])
            ->assertSessionHas('alert-success');
        $this->actingAs($this->usuario())->patch("/turmas/{$turma->id}/frequencia/{$chamada->id}/data", ['data' => '2026-10-30'])
            ->assertForbidden();

        $this->assertSame('2026-10-28', $chamada->fresh()->data->toDateString());
    }

    public function test_chamada_de_hoje_e_unica_por_data(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-27 09:00:00', 'America/Sao_Paulo'));
        $professor = $this->usuario('professor');
        $turma = $this->turma('semestral', $professor);

        $this->actingAs($professor)->get("/turmas/{$turma->id}/frequencia/hoje");
        $this->actingAs($professor)->get("/turmas/{$turma->id}/frequencia/hoje");

        $this->assertSame(1, Chamada::where('turma_id', $turma->id)->count());
    }

    public function test_chamada_ignora_matricula_de_outra_turma(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-27 09:00:00', 'America/Sao_Paulo'));
        $professor = $this->usuario('professor');
        $turma = $this->turma('semestral', $professor);
        $outra = $this->matricula($this->turma('semestral'), $this->usuario());

        $this->actingAs($professor)->get("/turmas/{$turma->id}/frequencia/hoje");
        $chamada = Chamada::where('turma_id', $turma->id)->firstOrFail();
        $this->actingAs($professor)
            ->patch("/turmas/{$turma->id}/frequencia/{$chamada->id}", ['presencas' => [$outra->id => Presenca::PRESENTE]])
            ->assertRedirect();

        $this->assertSame(0, Presenca::count());
    }

    public function test_minhas_turmas_lista_somente_as_turmas_do_professor(): void
    {
        $professor = $this->usuario('professor');
        $minha = $this->turma('semestral', $professor);
        $this->turma('semestral', $this->usuario('professor'));

        $this->actingAs($professor)->get('/minhas-turmas')->assertOk()
            ->assertViewHas('turmas', fn ($turmas) => $turmas->pluck('id')->all() === [$minha->id]);
    }

    public function test_turma_so_aceita_professor_cadastrado(): void
    {
        $turma = $this->turma('semestral');

        $this->actingAs($this->usuario('gestor-cursos'))
            ->post('/turmas', ['oferecimento_id' => $turma->oferecimento_id, 'professor_id' => $this->usuario()->id])
            ->assertSessionHasErrors('professor_id');
    }

    public function test_seeder_vincula_professores_pelo_codpes(): void
    {
        $this->seed(ProfessorSeeder::class);

        $id = ProfessorSeeder::idPorNome('Womualy - T-1 Alongamento');
        $this->assertSame(7475712, User::find($id)->codpes);
        $this->assertTrue(User::find($id)->hasRole('professor'));
        $this->assertSame(User::where('codpes', 1343680)->value('id'), ProfessorSeeder::idPorNome('Patricia Sakai  - T-1'));
        $this->assertSame(User::where('codpes', 608780)->value('id'), ProfessorSeeder::idPorNome('Bezerra -  T-1 Caminhada Longa'));
    }
}
