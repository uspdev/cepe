<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use Uspdev\SenhaunicaSocialite\Http\Controllers\SenhaunicaController;

class LoginLocalTest extends TestCase
{
    use RefreshDatabase;

    private function local(array $attrs = []): User
    {
        return User::factory()->create($attrs + ['email' => 'externo@exemplo.com', 'password' => 'Senha1234', 'local' => true]);
    }

    public function test_pagina_exibe_senha_unica_e_formulario_local(): void
    {
        $this->get('/entrar')->assertOk()
            ->assertSee(route('login'), false)
            ->assertSee('Entrar com Senha Única')
            ->assertSee('name="password"', false)
            ->assertSee(route('cadastro'), false);
    }

    public function test_logado_e_redirecionado(): void
    {
        $this->actingAs($this->local())->get('/entrar')->assertRedirect('/');
    }

    public function test_login_local_volta_a_pagina_pretendida_e_concede_user(): void
    {
        $user = $this->local();

        $this->get('/perfil')->assertRedirect('/entrar');
        $this->post('/entrar', ['email' => 'Externo@Exemplo.com ', 'password' => 'Senha1234'])->assertRedirect('/perfil');

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->hasPermissionTo('user', User::$hierarquiaNs));
    }

    public function test_senha_errada(): void
    {
        $this->local();

        $this->from('/entrar')->post('/entrar', ['email' => 'externo@exemplo.com', 'password' => 'errada'])
            ->assertRedirect('/entrar')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_usuario_senha_unica_nao_entra_pelo_login_local(): void
    {
        $this->local(['local' => false, 'codpes' => 123456]);

        $this->post('/entrar', ['email' => 'externo@exemplo.com', 'password' => 'Senha1234'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_rotas_do_pacote_permanecem(): void
    {
        $this->assertSame(SenhaunicaController::class.'@redirectToProvider', Route::getRoutes()->getByName('login')->getActionName());
        $this->assertNotNull(Route::getRoutes()->getByName('loginlocal'));
        $this->assertNotNull(Route::getRoutes()->getByName('logout'));
    }
}
