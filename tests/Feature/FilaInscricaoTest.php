<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\Fila;
use App\Models\Matricula;
use App\Models\Oferecimento;
use App\Models\Perfil;
use App\Models\PeriodoOferecimento;
use App\Models\Turma;
use App\Models\User;
use App\Models\VagaTurma;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class FilaInscricaoTest extends TestCase
{
    use RefreshDatabase;

    private Turma $turma;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::define('admin', fn () => false);
        Gate::define('user', fn () => true);

        $atividade = Atividade::create(['nome' => 'Evento X', 'descricao' => 'd', 'tipo' => 'eventos', 'status' => 'ativo']);
        $oferecimento = Oferecimento::create(['atividade_id' => $atividade->id, 'periodo' => '01-2026', 'pagamento' => 'gratuito']);
        PeriodoOferecimento::create([
            'oferecimento_id' => $oferecimento->id,
            'perfil' => 'externa',
            'inicio' => now()->subDay(),
            'fim' => now()->addDay(),
        ]);

        $this->turma = Turma::create(['oferecimento_id' => $oferecimento->id]);
        VagaTurma::create(['turma_id' => $this->turma->id, 'perfil' => 'tax_ex', 'quantidade' => 1]);
    }

    private function usuario(): User
    {
        $user = User::factory()->create();
        Perfil::create([
            'user_id' => $user->id,
            'sexo' => 'f',
            'data_nascimento' => '1990-05-10',
            'cpf' => '5299822472' . $user->id,
            'rg' => '123456789',
            'telefone' => '1134567890',
            'emergencia_nome' => 'Maria',
            'emergencia_telefone' => '11987654321',
            'cep' => '05508030',
            'estado' => 'SP',
            'cidade' => 'São Paulo',
            'bairro' => 'Butantã',
            'endereco' => 'Rua do Matão, 1',
        ]);

        return $user;
    }

    private function payload(): array
    {
        return [
            'perfil' => 'tax_ex',
            'image_use_authorization' => 'autoriza',
            'regulamento' => '1',
        ];
    }

    private function entrarNaFila(User $user, string $perfil = 'tax_ex'): void
    {
        $this->actingAs($user)->post("/inscricoes/{$this->turma->id}/fila", ['perfil' => $perfil]);
    }

    /** Ocupa a única vaga com uma matrícula direta. */
    private function ocuparVaga(): User
    {
        $ocupante = $this->usuario();
        $this->actingAs($ocupante)->get("/inscricoes/{$this->turma->id}");
        $this->actingAs($ocupante)->post("/inscricoes/{$this->turma->id}", $this->payload())->assertSessionHasNoErrors();

        return $ocupante;
    }

    public function test_todo_usuario_que_abre_inscricao_entra_na_fila_para_metrica(): void
    {
        $a = $this->usuario();

        $this->actingAs($a)->get("/inscricoes/{$this->turma->id}")->assertOk();

        // registro de interesse criado mesmo com vaga livre; perfil único é
        // gravado direto, sem exigir escolha
        $fila = Fila::ativasDaTurma($this->turma)->where('user_id', $a->id)->first();
        $this->assertSame(1, $fila->posicao);
        $this->assertSame('tax_ex', $fila->perfil);

        // recarregar é idempotente: mesma entrada
        $this->actingAs($a)->get("/inscricoes/{$this->turma->id}");
        $this->assertSame(1, Fila::ativasDaTurma($this->turma)->count());
    }

    public function test_vaga_livre_mostra_formulario_sem_botao_de_fila(): void
    {
        $a = $this->usuario();

        $this->actingAs($a)->get("/inscricoes/{$this->turma->id}")
            ->assertSee('data-fila-state="vez"', false);

        $this->actingAs($a)->post("/inscricoes/{$this->turma->id}", $this->payload())->assertSessionHasNoErrors();
        $this->assertSame('tax_ex', Matricula::first()->perfil);
        $this->assertSame(0, Fila::ativasDaTurma($this->turma)->count());

        // o perfil escolhido no formulário é gravado na entrada da fila (métrica de interesse)
        $this->assertSame('tax_ex', Fila::where('user_id', $a->id)->value('perfil'));
    }

    public function test_vaga_esgotada_com_perfil_unico_ja_entra_aguardando(): void
    {
        $this->ocuparVaga();
        $a = $this->usuario();

        // perfil único: registrado automaticamente, sem passar por "Entrar na fila"
        $this->actingAs($a)->get("/inscricoes/{$this->turma->id}")
            ->assertOk()
            ->assertSee('data-fila-state="aguardando"', false)
            ->assertSee('Vagas esgotadas', false);

        $fila = Fila::ativasDaTurma($this->turma)->where('user_id', $a->id)->first();
        $this->assertSame('tax_ex', $fila->perfil);
    }

    public function test_vaga_esgotada_com_multiplos_perfis_mostra_escolha(): void
    {
        PeriodoOferecimento::create([
            'oferecimento_id' => $this->turma->oferecimento_id,
            'perfil' => 'alumni',
            'inicio' => now()->subDay(),
            'fim' => now()->addDay(),
        ]);
        VagaTurma::create(['turma_id' => $this->turma->id, 'perfil' => 'tax_alumni', 'quantidade' => 1]);

        $this->ocuparVaga(); // ocupa tax_ex
        $a = $this->usuario(); // credenciado: elegível a tax_ex e tax_alumni
        Perfil::where('user_id', $a->id)->update(['vinculo_usp' => 'Alumni']);

        // ocupa também tax_alumni para esgotar todos os buckets
        $b = $this->usuario();
        Perfil::where('user_id', $b->id)->update(['vinculo_usp' => 'Alumni']);
        $this->actingAs($b)->post("/inscricoes/{$this->turma->id}", array_merge($this->payload(), ['perfil' => 'tax_alumni']))
            ->assertSessionHasNoErrors();

        // A tem dois perfis elegíveis e nenhuma vaga livre: escolhe e entra na fila
        $this->actingAs($a)->get("/inscricoes/{$this->turma->id}")
            ->assertOk()
            ->assertSee('data-fila-state="fora"', false)
            ->assertSee('Entrar na fila', false)
            ->assertSee('id="fila_perfil_tax_ex"', false)
            ->assertSee('id="fila_perfil_tax_alumni"', false);

        $this->entrarNaFila($a);

        $fila = Fila::ativasDaTurma($this->turma)->where('user_id', $a->id)->first();
        $this->assertSame('tax_ex', $fila->perfil);

        $this->actingAs($a)->get("/inscricoes/{$this->turma->id}")
            ->assertSee('data-fila-state="aguardando"', false);
    }

    public function test_fila_impede_inscricao_direta_e_ordena_a_vez(): void
    {
        $this->ocuparVaga();
        $a = $this->usuario();
        $b = $this->usuario();

        $this->entrarNaFila($a);
        $this->entrarNaFila($b);

        // B não pula a fila; quem chega de fora também não (fila ocupa a "vaga")
        $this->actingAs($b)->post("/inscricoes/{$this->turma->id}", $this->payload())->assertSessionHasErrors();

        Matricula::first()->update(['status' => Matricula::CANCELADA]);

        // vaga liberada: A é head, B segue aguardando e terceiro de fora não fura
        $this->actingAs($a)->get("/inscricoes/{$this->turma->id}")->assertSee('data-fila-state="vez"', false);
        $this->actingAs($b)->get("/inscricoes/{$this->turma->id}")->assertSee('data-fila-state="aguardando"', false);
        $this->actingAs($this->usuario())->post("/inscricoes/{$this->turma->id}", $this->payload())->assertSessionHasErrors();

        $this->actingAs($a)->post("/inscricoes/{$this->turma->id}", $this->payload())->assertSessionHasNoErrors();
        $this->assertSame(0, Fila::ativasDaTurma($this->turma)->where('user_id', $a->id)->count());
    }

    public function test_proximo_da_fila_assume_quando_heartbeat_expira(): void
    {
        $this->ocuparVaga();
        $a = $this->usuario();
        $b = $this->usuario();

        $this->entrarNaFila($a);
        $this->entrarNaFila($b);
        Matricula::first()->update(['status' => Matricula::CANCELADA]);

        // A abandona (heartbeat expira) e B vira head
        Fila::ativasDaTurma($this->turma)->where('user_id', $a->id)
            ->update(['updated_at' => now()->subSeconds(config('cepe.fila_ttl') + 10)]);

        $this->actingAs($b)->get("/inscricoes/{$this->turma->id}")->assertSee('data-fila-state="vez"', false);
        $this->actingAs($b)->post("/inscricoes/{$this->turma->id}", $this->payload())->assertSessionHasNoErrors();
        $this->assertSame(1, Matricula::ativas()->count());
    }

    public function test_perfis_diferentes_nao_disputam_o_mesmo_bucket(): void
    {
        // abre também o período alumni e adiciona o bucket correspondente
        PeriodoOferecimento::create([
            'oferecimento_id' => $this->turma->oferecimento_id,
            'perfil' => 'alumni',
            'inicio' => now()->subDay(),
            'fim' => now()->addDay(),
        ]);
        VagaTurma::create(['turma_id' => $this->turma->id, 'perfil' => 'tax_alumni', 'quantidade' => 1]);

        $this->ocuparVaga(); // ocupa tax_ex

        $a = $this->usuario(); // entra na fila de tax_ex
        $b = $this->usuario(); // credenciado: entra na fila de tax_alumni
        Perfil::where('user_id', $b->id)->update(['vinculo_usp' => 'Alumni']);

        $this->entrarNaFila($a);
        $this->entrarNaFila($b, 'tax_alumni');

        // B é head em tax_alumni mesmo estando atrás na fila global
        $this->actingAs($b)->get("/inscricoes/{$this->turma->id}")->assertSee('data-fila-state="vez"', false);
        $this->actingAs($b)->post("/inscricoes/{$this->turma->id}", array_merge($this->payload(), ['perfil' => 'tax_alumni']))->assertSessionHasNoErrors();
        $this->assertSame('tax_alumni', Matricula::ativas()->where('user_id', $b->id)->value('perfil'));
    }
}
