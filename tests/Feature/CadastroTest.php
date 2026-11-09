<?php

namespace Tests\Feature;

use App\Models\Perfil;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CadastroTest extends TestCase
{
    use RefreshDatabase;

    private function dados(array $extra = []): array
    {
        return $extra + [
            'name' => 'Pessoa Externa',
            'email' => 'Pessoa@Exemplo.com',
            'password' => 'Senha1234',
            'password_confirmation' => 'Senha1234',
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

    public function test_formulario_exibe_acesso_e_perfil(): void
    {
        $this->get('/cadastro')->assertOk()
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('name="cpf"', false)
            ->assertSee('name="emergencia_nome"', false);
    }

    public function test_cadastro_cria_conta_local_com_perfil_e_autentica(): void
    {
        $this->post('/cadastro', $this->dados())->assertRedirect('/')->assertSessionHasNoErrors();

        $user = User::where('email', 'pessoa@exemplo.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, (int) $user->local);
        $this->assertNull($user->codpes);
        $this->assertTrue(password_verify('Senha1234', $user->password));
        $this->assertTrue($user->hasPermissionTo('user', User::$hierarquiaNs));

        $perfil = $user->perfil;
        $this->assertSame('52998224725', $perfil->cpf);
        $this->assertSame('1990-05-10', $perfil->data_nascimento->toDateString());
        $this->assertTrue($perfil->completo());
    }

    public function test_sem_cpf_dispensa_cpf(): void
    {
        $dados = $this->dados(['sem_cpf' => 1]);
        unset($dados['cpf']);

        $this->post('/cadastro', $dados)->assertSessionHasNoErrors();
        $this->assertNull(User::firstWhere('email', 'pessoa@exemplo.com')->perfil->cpf);
    }

    public function test_validacoes(): void
    {
        User::factory()->create(['email' => 'pessoa@exemplo.com']);
        $this->post('/cadastro', $this->dados())->assertSessionHasErrors('email');

        $this->post('/cadastro', $this->dados(['email' => 'aluno@usp.br']))->assertSessionHasErrors('email');
        $this->post('/cadastro', $this->dados(['email' => 'docente@fflch.usp.br']))->assertSessionHasErrors('email');
        $this->post('/cadastro', $this->dados(['email' => 'novo@exemplo.com', 'password_confirmation' => 'Outra1234']))->assertSessionHasErrors('password');
        $this->post('/cadastro', $this->dados(['email' => 'novo@exemplo.com', 'password' => 'fraca', 'password_confirmation' => 'fraca']))->assertSessionHasErrors('password');
        $this->post('/cadastro', $this->dados(['email' => 'novo@exemplo.com', 'cpf' => '111.111.111-11']))->assertSessionHasErrors('cpf');
        $this->post('/cadastro', $this->dados(['email' => 'novo@exemplo.com', 'telefone' => '']))->assertSessionHasErrors('telefone');

        $this->assertGuest();
        $this->assertSame(1, User::count());
    }

    public function test_cpf_unico(): void
    {
        Perfil::create(['user_id' => User::factory()->create()->id, 'cpf' => '52998224725']);

        $this->post('/cadastro', $this->dados())->assertSessionHasErrors('cpf');
        $this->assertGuest();
    }
}
