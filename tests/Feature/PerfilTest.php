<?php

namespace Tests\Feature;

use App\Models\Perfil;
use App\Models\User;
use App\Rules\Cpf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PerfilTest extends TestCase
{
    use RefreshDatabase;

    private function gates(): void
    {
        Gate::define('admin', fn ($u) => $u->email === 'admin@teste.com');
        Gate::define('user', fn () => true);
    }

    private function dados(array $extra = []): array
    {
        return $extra + [
            'sexo' => 'f',
            'data_nascimento' => '10/05/1990',
            'cpf' => '529.982.247-25',
            'rg' => '12.345.678-9',
            'telefone' => '(11) 3456-7890',
            'emergencia_nome' => 'Maria',
            'emergencia_telefone' => '(11) 98765-4321',
            'cep' => '05508-030',
            'estado' => 'SP',
            'cidade' => 'São Paulo',
            'bairro' => 'Butantã',
            'endereco' => 'Rua do Matão, 1',
        ];
    }

    public function test_cpf_rule(): void
    {
        $this->assertTrue(Cpf::valido('529.982.247-25'));
        $this->assertFalse(Cpf::valido('529.982.247-24'));
        $this->assertFalse(Cpf::valido('111.111.111-11'));
        $this->assertFalse(Cpf::valido('123'));
    }

    public function test_anonimo_redirecionado_e_usuario_ve_proprio_perfil(): void
    {
        $this->gates();
        $this->get('/perfil')->assertRedirect();
        $this->actingAs(User::factory()->create())->get('/perfil')->assertOk()->assertSee('Meu perfil');
    }

    public function test_salva_perfil_normalizando_mascaras(): void
    {
        $this->gates();
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/perfil', $this->dados())->assertRedirect('/perfil')->assertSessionHasNoErrors();

        $perfil = $user->fresh()->perfil;
        $this->assertSame('52998224725', $perfil->cpf);
        $this->assertSame('1134567890', $perfil->telefone);
        $this->assertSame('05508030', $perfil->cep);
        $this->assertSame('1990-05-10', $perfil->data_nascimento->toDateString());
        $this->assertTrue($perfil->completo());
    }

    public function test_validacoes(): void
    {
        $this->gates();
        $user = User::factory()->create();
        $ator = $this->actingAs($user);

        $ator->patch('/perfil', $this->dados(['cpf' => '111.111.111-11']))->assertSessionHasErrors('cpf');
        $ator->patch('/perfil', $this->dados(['cpf' => '529.982.247-25', 'sem_cpf' => 1]))->assertSessionHasErrors('cpf');
        $ator->patch('/perfil', $this->dados(['estado' => 'XX']))->assertSessionHasErrors('estado');
        $ator->patch('/perfil', $this->dados(['data_nascimento' => now()->addDay()->format('d/m/Y')]))->assertSessionHasErrors('data_nascimento');
        $ator->patch('/perfil', $this->dados(['sexo' => 'x']))->assertSessionHasErrors('sexo');
    }

    public function test_sem_cpf_dispensa_cpf(): void
    {
        $this->gates();
        $user = User::factory()->create();
        $dados = $this->dados(['sem_cpf' => 1]);
        unset($dados['cpf']);

        $this->actingAs($user)->patch('/perfil', $dados)->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->perfil->completo());
    }

    public function test_cpf_unico(): void
    {
        $this->gates();
        $outro = User::factory()->create();
        Perfil::create(['user_id' => $outro->id, 'cpf' => '52998224725']);

        $this->actingAs(User::factory()->create())->patch('/perfil', $this->dados())->assertSessionHasErrors('cpf');
    }

    public function test_usuario_usp_nao_altera_nome_e_email(): void
    {
        $this->gates();
        $user = User::factory()->create(['name' => 'Original', 'email' => 'orig@usp.br', 'local' => false]);

        $this->actingAs($user)->patch('/perfil', $this->dados(['name' => 'Hack', 'email' => 'hack@x.com']))->assertSessionHasNoErrors();

        $this->assertSame('Original', $user->fresh()->name);
        $this->assertSame('orig@usp.br', $user->fresh()->email);
    }

    public function test_usuario_local_altera_nome_email_e_senha(): void
    {
        $this->gates();
        $user = User::factory()->create(['local' => true]);

        $this->actingAs($user)->patch('/perfil', $this->dados(['name' => 'Novo Nome', 'email' => 'novo@x.com', 'password' => 'Senha1234', 'password_confirmation' => 'Senha1234']))
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Novo Nome', $user->name);
        $this->assertSame('novo@x.com', $user->email);
        $this->assertTrue(password_verify('Senha1234', $user->password));
    }

    public function test_campos_restritos_so_admin(): void
    {
        $this->gates();
        $user = User::factory()->create();
        $restritos = ['credito' => 50, 'numero_cepeusp' => '926300001', 'vinculo_usp' => 'Docente'];

        $this->actingAs($user)->patch('/perfil', $this->dados($restritos))->assertSessionHasNoErrors();
        $perfil = $user->fresh()->perfil;
        $this->assertSame(0, $perfil->credito);
        $this->assertNull($perfil->numero_cepeusp);

        $admin = User::factory()->create(['email' => 'admin@teste.com']);
        $this->actingAs($admin)->patch("/usuarios/{$user->id}/perfil", $this->dados($restritos))->assertSessionHasNoErrors();
        $perfil = $user->fresh()->perfil;
        $this->assertSame(50, $perfil->credito);
        $this->assertSame('926300001', $perfil->numero_cepeusp);
    }

    public function test_autorizacao_entre_usuarios(): void
    {
        $this->gates();
        $alvo = User::factory()->create();
        $outro = User::factory()->create();
        $admin = User::factory()->create(['email' => 'admin@teste.com']);

        $this->actingAs($outro)->get("/usuarios/{$alvo->id}/perfil")->assertForbidden();
        $this->actingAs($outro)->patch("/usuarios/{$alvo->id}/perfil", $this->dados())->assertForbidden();
        $this->actingAs($admin)->get("/usuarios/{$alvo->id}/perfil")->assertOk();
    }

    public function test_banner_de_perfil_incompleto(): void
    {
        $this->gates();
        $user = User::factory()->create();

        $this->actingAs($user)->get('/meus-atestados')->assertSee('Seu perfil está incompleto');
        $this->actingAs($user)->get('/perfil')->assertDontSee('Seu perfil está incompleto');

        Perfil::create($this->dados(['cpf' => '52998224725', 'telefone' => '1134567890', 'emergencia_telefone' => '11987654321', 'cep' => '05508030', 'data_nascimento' => '1990-05-10', 'user_id' => $user->id]));
        $this->actingAs($user->fresh())->get('/meus-atestados')->assertDontSee('Seu perfil está incompleto');
    }

    public function test_dados_administrativos_visiveis_mas_nao_editaveis_para_usuario(): void
    {
        $this->gates();
        $user = User::factory()->create();
        Perfil::create(['user_id' => $user->id, 'vinculo_usp' => 'Docente', 'unidade_usp' => 'EEFE - Escola', 'numero_cepeusp' => '926300001', 'credito' => 20]);

        $html = $this->actingAs($user)->get('/perfil')->assertOk()->assertSee('Docente')->assertSee('EEFE - Escola')->assertSee('926300001')->getContent();
        $this->assertMatchesRegularExpression('/name="vinculo_usp"\s+disabled/', $html);
        $this->assertMatchesRegularExpression('/name="credito"\s+disabled/', $html);

        $admin = User::factory()->create(['email' => 'admin@teste.com']);
        $html = $this->actingAs($admin)->get("/usuarios/{$user->id}/perfil")->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression('/name="vinculo_usp"\s+disabled/', $html);
    }
}
