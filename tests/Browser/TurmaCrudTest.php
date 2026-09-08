<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\Turma;

class TurmaCrudTest extends DuskTestCase
{
    use DatabaseMigrations;
    public function test_crud_turmas(): void
    {
        $this->browse(function (Browser $browser) {
            // Login
            $browser->visit('/')
                ->clickLink('Entrar')
                ->waitFor('#loginUsuario')
                ->typeSlowly('#loginUsuario', '111111')
                ->press('Login');
                
            // Create
            $browser->visit('/turmas/create')
                ->typeSlowly('oferecimento_id', 'expedita')
                ->press('Enviar')
                ->assertPathIs('/turmas')
                ->assertSee('expedita');

            // Read Index (search)
            $browser->visit('/turmas')
                ->type('search', 'expedita')
                ->press('Pesquisar')
                ->assertSee('expedita')
                ->type('search', 'TextoInexistenteXYZ')
                ->press('Pesquisar')
                ->assertDontSee('expedita');

            // Read Show
            $browser->visit('/turmas')
                ->clickLink('expedita')
;

            // Update
            $browser->clickLink('Editar')
                ->typeSlowly('oferecimento_id', 'expedita - Editado')
                ->press('Enviar')
                ->assertSee('expedita - Editado');

            // Delete
            $browser->press('Apagar')
                ->acceptDialog()
                ->assertPathIs('/turmas')
                ->assertDontSee('expedita - Editado');
        });
    }
}
