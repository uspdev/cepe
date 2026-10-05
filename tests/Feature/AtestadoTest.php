<?php

namespace Tests\Feature;

use App\Models\Atestado;
use App\Models\Perfil;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AtestadoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Gate::define('admin', fn ($u) => $u->email === 'admin@teste.com');

        return User::factory()->create(['email' => 'admin@teste.com']);
    }

    private function comNascimento(User $user, int $idade): User
    {
        Perfil::create(['user_id' => $user->id, 'data_nascimento' => now()->subYears($idade)->subDay()]);

        return $user;
    }

    private function enviar(User $user, array $extra = [])
    {
        return $this->actingAs($user)->post('/meus-atestados', $extra + [
            'tipo' => 'atestado_medico',
            'emitido_em' => now()->subDay()->format('d/m/Y'),
            'arquivo' => UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'),
        ]);
    }

    public function test_usuario_envia_atestado_com_arquivo(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->enviar($user)->assertRedirect('/meus-atestados');

        $atestado = Atestado::first();
        $this->assertSame('em_analise', $atestado->status);
        Storage::disk('local')->assertExists($atestado->arquivo);
    }

    public function test_arquivo_obrigatorio_exceto_parq(): void
    {
        $user = User::factory()->create();
        $this->enviar($user, ['arquivo' => null])->assertSessionHasErrors('arquivo');
    }

    public function test_parq_com_sim_exige_termo(): void
    {
        $user = $this->comNascimento(User::factory()->create(), 30);
        $respostas = array_fill(1, 7, 'nao');
        $respostas[3] = 'sim';
        $base = ['tipo' => 'parq', 'respostas' => $respostas];

        $this->actingAs($user)->post('/meus-atestados', $base)->assertSessionHasErrors('termo_aceito');
        $this->actingAs($user)->post('/meus-atestados', $base + ['termo_aceito' => 1])->assertSessionHasNoErrors();

        $this->assertTrue(Atestado::first()->parq->termo_aceito);
        $this->assertTrue(Atestado::first()->emitido_em->isToday());
    }

    public function test_parq_exige_todas_as_respostas(): void
    {
        $user = $this->comNascimento(User::factory()->create(), 30);
        $this->actingAs($user)->post('/meus-atestados', ['tipo' => 'parq', 'respostas' => [1 => 'nao']])
            ->assertSessionHasErrors('respostas.2');
    }

    public function test_arquivo_invalido_rejeitado(): void
    {
        $user = User::factory()->create();
        $this->enviar($user, ['arquivo' => UploadedFile::fake()->create('a.exe', 10)])->assertSessionHasErrors('arquivo');
    }

    public function test_usuario_nao_baixa_arquivo_alheio(): void
    {
        Storage::fake('local');
        $this->admin();
        $dono = User::factory()->create();
        $outro = User::factory()->create();
        $this->enviar($dono);
        $atestado = Atestado::first();

        $this->actingAs($outro)->get("/atestados/{$atestado->id}/arquivo")->assertForbidden();
        $this->actingAs($dono)->get("/atestados/{$atestado->id}/arquivo")->assertOk();
    }

    public function test_nao_admin_nao_analisa(): void
    {
        $this->admin();
        $user = User::factory()->create();
        Atestado::create(['user_id' => $user->id, 'tipo' => 'atestado_medico', 'emitido_em' => now(), 'status' => 'em_analise']);

        $this->actingAs($user)->patch('/atestados/1/analise', ['status' => 'aprovado'])->assertForbidden();
    }

    public function test_aprovacao_define_validade_por_tipo(): void
    {
        config(['cepe.atestados.validade_meses.exame_dermatologico' => 6]);
        $admin = $this->admin();
        $user = User::factory()->create();
        $a = Atestado::create(['user_id' => $user->id, 'tipo' => 'exame_dermatologico', 'emitido_em' => '2026-01-10', 'status' => 'em_analise']);

        $this->actingAs($admin)->patch("/atestados/{$a->id}/analise", ['status' => 'aprovado'])->assertRedirect();

        $a->refresh();
        $this->assertSame('2026-07-10', $a->valido_ate->toDateString());
        $this->assertSame($admin->id, $a->analisado_por);
        $this->assertTrue(Atestado::valido($user, 'exame_dermatologico') || $a->valido_ate->isPast());
    }

    public function test_reprovar_exige_observacao(): void
    {
        $admin = $this->admin();
        $a = Atestado::create(['user_id' => User::factory()->create()->id, 'tipo' => 'atestado_medico', 'emitido_em' => now(), 'status' => 'em_analise']);

        $this->actingAs($admin)->patch("/atestados/{$a->id}/analise", ['status' => 'reprovado'])->assertSessionHasErrors('observacao');
    }

    public function test_comando_marca_vencidos(): void
    {
        $user = User::factory()->create();
        $velho = Atestado::create(['user_id' => $user->id, 'tipo' => 'parq', 'emitido_em' => now()->subYear(), 'valido_ate' => now()->subDay(), 'status' => 'aprovado']);
        $ok = Atestado::create(['user_id' => $user->id, 'tipo' => 'atestado_medico', 'emitido_em' => now(), 'valido_ate' => now()->addMonth(), 'status' => 'aprovado']);

        $this->artisan('atestados:vencer')->assertSuccessful();

        $this->assertSame('vencido', $velho->fresh()->status);
        $this->assertSame('aprovado', $ok->fresh()->status);
        $this->assertTrue(Atestado::valido($user, 'atestado_medico'));
        $this->assertFalse(Atestado::valido($user, 'parq'));
    }

    public function test_anonimo_redirecionado(): void
    {
        $this->get('/meus-atestados')->assertRedirect();
        $this->get('/atestados')->assertRedirect();
    }

    public function test_formulario_separado_por_tipo(): void
    {
        $this->admin();
        Gate::define('user', fn () => true);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/meus-atestados/create?tipo=atestado_medico')->assertOk()->assertSee('name="arquivo"', false)->assertDontSee('respostas[1]', false);
        $this->actingAs($user)->get('/meus-atestados/create?tipo=exame_dermatologico')->assertOk()->assertSee('value="exame_dermatologico"', false)->assertSee('name="arquivo"', false);
        $this->actingAs($user)->get('/meus-atestados/create?tipo=parq')->assertOk()->assertSee('respostas[1]', false)->assertDontSee('name="arquivo"', false);
        $this->actingAs($user)->get('/meus-atestados/create?tipo=invalido')->assertNotFound();
    }

    public function test_filtros_da_listagem(): void
    {
        $admin = $this->admin();
        Gate::define('user', fn () => true);
        $ana = User::factory()->create(['name' => 'Ana Souza', 'email' => 'ana@x.com']);
        $bia = User::factory()->create(['name' => 'Bia 100%', 'email' => 'bia@x.com']);
        $base = ['emitido_em' => now()->subMonths(3)];
        Atestado::create($base + ['user_id' => $ana->id, 'tipo' => 'atestado_medico', 'status' => 'em_analise']);
        Atestado::create($base + ['user_id' => $ana->id, 'tipo' => 'parq', 'status' => 'aprovado', 'valido_ate' => now()->addMonth()]);
        Atestado::create($base + ['user_id' => $bia->id, 'tipo' => 'parq', 'status' => 'aprovado', 'valido_ate' => now()->subDay()]);
        Atestado::create($base + ['user_id' => $bia->id, 'tipo' => 'exame_dermatologico', 'status' => 'vencido', 'valido_ate' => now()->subMonth()]);
        Atestado::create($base + ['user_id' => $bia->id, 'tipo' => 'atestado_medico', 'status' => 'reprovado']);

        $conta = fn (string $qs) => $this->actingAs($admin)->get('/atestados'.$qs)->assertOk()->viewData('atestados')->total();

        $this->assertSame(1, $conta(''), 'padrão: em análise');
        $this->assertSame(5, $conta('?status=todos'), 'todos');
        $this->assertSame(5, $conta('?status='), 'vazio equivale a todos');
        $this->assertSame(1, $conta('?status=aprovado'), 'aprovado exclui expirado');
        $this->assertSame(2, $conta('?status=vencido'), 'vencido inclui aprovado expirado');
        $this->assertSame(1, $conta('?status=reprovado'));
        $this->assertSame(2, $conta('?status=todos&tipo=parq'));
        $this->assertSame(1, $conta('?status=vencido&tipo=parq'));
        $this->assertSame(2, $conta('?status=todos&search=ana'));
        $this->assertSame(3, $conta('?status=todos&search=bia@x'));
        $this->assertSame(3, $conta('?status=todos&search=%25'), '% literal acha so quem tem % no nome');
        $this->assertSame(0, $conta('?status=todos&search=A_a'), '_ nao funciona como curinga');
        $this->assertSame(3, $conta('?status=todos&search=100%25'));
    }

    public function test_parq_exige_data_de_nascimento_no_perfil(): void
    {
        $user = User::factory()->create();
        $dados = ['tipo' => 'parq', 'respostas' => array_fill(1, 7, 'nao')];

        $this->actingAs($user)->post('/meus-atestados', $dados)->assertSessionHasErrors('tipo');
        $this->assertSame(0, Atestado::count());
    }

    public function test_parq_respeita_faixa_etaria(): void
    {
        $dados = ['tipo' => 'parq', 'respostas' => array_fill(1, 7, 'nao')];

        foreach ([14, 70] as $idade) {
            $user = $this->comNascimento(User::factory()->create(), $idade);
            $this->actingAs($user)->post('/meus-atestados', $dados)->assertSessionHasErrors('tipo');
        }
        foreach ([15, 69] as $idade) {
            $user = $this->comNascimento(User::factory()->create(), $idade);
            $this->actingAs($user)->post('/meus-atestados', $dados)->assertSessionHasNoErrors();
        }
        $this->assertSame(2, Atestado::count());
    }

    public function test_parq_nao_e_oferecido_fora_da_faixa_etaria(): void
    {
        $this->admin();
        Gate::define('user', fn () => true);
        $velho = $this->comNascimento(User::factory()->create(), 85);
        $jovem = $this->comNascimento(User::factory()->create(), 30);

        $this->actingAs($velho)->get('/meus-atestados/create?tipo=parq')
            ->assertRedirect('/meus-atestados/create?tipo=atestado_medico')
            ->assertSessionHas('alert-warning');
        $this->actingAs($velho)->get('/meus-atestados/create?tipo=atestado_medico')->assertOk()->assertDontSee('tipo=parq', false);
        $this->actingAs($velho)->get('/meus-atestados')->assertOk()->assertDontSee('tipo=parq', false);

        $this->actingAs($jovem)->get('/meus-atestados/create?tipo=parq')->assertOk()->assertSee('respostas[1]', false);
        $this->actingAs($jovem)->get('/meus-atestados')->assertSee('tipo=parq', false);
    }
}
