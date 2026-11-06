<?php

namespace Tests\Feature;

use App\Models\CarteiraTaxa;
use App\Models\User;
use App\Support\Papeis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CarteiraTaxaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::define('admin', fn ($u) => $u->email === 'admin@teste.com');
        Gate::define('user', fn () => true);
        Papeis::sincronizar();
    }

    private function usuario(?string $papel = null): User
    {
        $user = User::factory()->create();
        $papel && $user->assignRole($papel);

        return $user;
    }

    private function categoria(): string
    {
        return array_key_first(config('cepe.carteiras.categorias'));
    }

    public function test_somente_sru_acessa_a_tela_de_taxas(): void
    {
        foreach ([null, 'gestor-cursos', 'gestor-eventos', 'professor'] as $papel) {
            $this->actingAs($this->usuario($papel))->get('/carteiras-taxas')->assertForbidden();
            $this->actingAs($this->usuario($papel))->put('/carteiras-taxas', ['taxas' => [$this->categoria() => ['valor' => 1]]])->assertForbidden();
        }

        $this->actingAs($this->usuario('sru'))->get('/carteiras-taxas')->assertOk()
            ->assertSee(config('cepe.carteiras.categorias.'.$this->categoria().'.rotulo'));
    }

    public function test_sru_define_taxas_e_descricao_usa_o_banco(): void
    {
        $sru = $this->usuario('sru');
        $categoria = $this->categoria();
        $padrao = config("cepe.carteiras.categorias.{$categoria}.taxa", '');
        $this->assertSame((string) $padrao, CarteiraTaxa::descricao($categoria));

        $this->actingAs($sru)->put('/carteiras-taxas', ['taxas' => [
            $categoria => ['valor' => '960', 'valor_dependente' => '480', 'observacao' => 'em 3 vezes no boleto ou Pix'],
        ]])->assertSessionHasNoErrors()->assertRedirect('/carteiras-taxas');

        $taxa = CarteiraTaxa::firstWhere('categoria', $categoria);
        $this->assertSame(960, $taxa->valor);
        $this->assertSame(480, $taxa->valor_dependente);
        $this->assertSame($sru->id, $taxa->atualizado_por);
        $this->assertSame('Taxa anual R$ 960,00 (dependente R$ 480,00) em 3 vezes no boleto ou Pix', CarteiraTaxa::descricao($categoria));
        $this->assertSame(1, CarteiraTaxa::count());
    }

    public function test_validacao_rejeita_valor_negativo_e_categoria_desconhecida(): void
    {
        $sru = $this->usuario('sru');

        $this->actingAs($sru)->put('/carteiras-taxas', ['taxas' => [$this->categoria() => ['valor' => '-1']]])
            ->assertSessionHasErrors('taxas.'.$this->categoria().'.valor');
        $this->actingAs($sru)->put('/carteiras-taxas', ['taxas' => ['inexistente' => ['valor' => '10']]])
            ->assertSessionHasErrors('taxas');
        $this->assertSame(0, CarteiraTaxa::count());
    }

    public function test_limpar_valor_volta_ao_texto_padrao(): void
    {
        $categoria = $this->categoria();
        CarteiraTaxa::create(['categoria' => $categoria, 'valor' => 73]);

        $this->actingAs($this->usuario('sru'))->put('/carteiras-taxas', ['taxas' => [$categoria => ['valor' => null]]])->assertSessionHasNoErrors();

        $this->assertNull(CarteiraTaxa::firstWhere('categoria', $categoria)->valor);
        $this->assertSame((string) config("cepe.carteiras.categorias.{$categoria}.taxa", ''), CarteiraTaxa::descricao($categoria));
    }
}
