<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\Atividade;

class AtividadeCrudTest extends DuskTestCase
{
    use DatabaseMigrations;
    public function test_crud_atividades(): void
    {
        $this->browse(function (Browser $browser) {
            // Login
            $browser->visit('/')
                ->clickLink('Entrar')
                ->waitFor('#loginUsuario')
                ->typeSlowly('#loginUsuario', '111111')
                ->press('Login');
                
            // Create
            $browser->visit('/atividades/create')
                ->typeSlowly('nome', 'Ea incidunt in unde et.')
                ->typeSlowly('descricao', 'Eum odit perspiciatis vero et. Vero ipsum perferendis voluptas.')
                ->press('Enviar')
                ->assertPathIs('/atividades')
                ->assertSee('Ea incidunt in unde et.');

            // Read Index (search)
            $browser->visit('/atividades')
                ->type('search', 'Ea incidunt in unde et.')
                ->press('Pesquisar')
                ->assertSee('Ea incidunt in unde et.')
                ->type('search', 'TextoInexistenteXYZ')
                ->press('Pesquisar')
                ->assertDontSee('Ea incidunt in unde et.');

            // Read Show
            $browser->visit('/atividades')
                ->clickLink('Ea incidunt in unde et.')
                ->assertSee('Eum odit perspiciatis vero et. Vero ipsum perferendis voluptas.');

            // Update
            $browser->clickLink('Editar')
                ->typeSlowly('nome', 'Ea incidunt in unde et. - Editado')
                ->press('Enviar')
                ->assertSee('Ea incidunt in unde et. - Editado');

            // Delete
            $browser->press('Apagar')
                ->acceptDialog()
                ->assertPathIs('/atividades')
                ->assertDontSee('Ea incidunt in unde et. - Editado');
        });
    }
}
