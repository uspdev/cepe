<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\Matricula;

class MatriculaCrudTest extends DuskTestCase
{
    use DatabaseMigrations;
    public function test_crud_matriculas(): void
    {
        $this->browse(function (Browser $browser) {
            // Login
            $browser->visit('/')
                ->clickLink('Entrar')
                ->waitFor('#loginUsuario')
                ->typeSlowly('#loginUsuario', '111111')
                ->press('Login');
                
            // Create
            $browser->visit('/matriculas/create')
                ->typeSlowly('a', 'ut')
                ->press('Enviar')
                ->assertPathIs('/matriculas')
                ->assertSee('ut');

            // Read Index (search)
            $browser->visit('/matriculas')
                ->type('search', 'ut')
                ->press('Pesquisar')
                ->assertSee('ut')
                ->type('search', 'TextoInexistenteXYZ')
                ->press('Pesquisar')
                ->assertDontSee('ut');

            // Read Show
            $browser->visit('/matriculas')
                ->clickLink('ut')
;

            // Update
            $browser->clickLink('Editar')
                ->typeSlowly('a', 'ut - Editado')
                ->press('Enviar')
                ->assertSee('ut - Editado');

            // Delete
            $browser->press('Apagar')
                ->acceptDialog()
                ->assertPathIs('/matriculas')
                ->assertDontSee('ut - Editado');
        });
    }
}
