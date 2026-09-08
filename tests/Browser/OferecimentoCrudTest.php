<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\Oferecimento;

class OferecimentoCrudTest extends DuskTestCase
{
    use DatabaseMigrations;
    public function test_crud_oferecimentos(): void
    {
        $this->browse(function (Browser $browser) {
            // Login
            $browser->visit('/')
                ->clickLink('Entrar')
                ->waitFor('#loginUsuario')
                ->typeSlowly('#loginUsuario', '111111')
                ->press('Login');
                
            // Create
            $browser->visit('/oferecimentos/create')
                ->typeSlowly('atividade_id', 'minima')
                ->press('Enviar')
                ->assertPathIs('/oferecimentos')
                ->assertSee('minima');

            // Read Index (search)
            $browser->visit('/oferecimentos')
                ->type('search', 'minima')
                ->press('Pesquisar')
                ->assertSee('minima')
                ->type('search', 'TextoInexistenteXYZ')
                ->press('Pesquisar')
                ->assertDontSee('minima');

            // Read Show
            $browser->visit('/oferecimentos')
                ->clickLink('minima')
;

            // Update
            $browser->clickLink('Editar')
                ->typeSlowly('atividade_id', 'minima - Editado')
                ->press('Enviar')
                ->assertSee('minima - Editado');

            // Delete
            $browser->press('Apagar')
                ->acceptDialog()
                ->assertPathIs('/oferecimentos')
                ->assertDontSee('minima - Editado');
        });
    }
}
