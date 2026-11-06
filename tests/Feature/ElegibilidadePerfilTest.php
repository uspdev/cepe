<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\Matricula;
use App\Models\Oferecimento;
use App\Models\Perfil;
use App\Models\PeriodoOferecimento;
use App\Models\TaxaTurma;
use App\Models\Turma;
use App\Models\User;
use App\Models\VagaTurma;
use App\Services\InscricaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElegibilidadePerfilTest extends TestCase
{
    use RefreshDatabase;

    private function turma(string $tipo = 'semestral', array $periodos = ['usp'], array $vagas = ['tax_in' => 1], ?array $perfisComTaxa = null): Turma
    {
        $atividade = Atividade::create(['nome' => 'Atividade', 'descricao' => 'd', 'tipo' => $tipo, 'status' => 'ativo']);
        $oferecimento = Oferecimento::create(['atividade_id' => $atividade->id, 'periodo' => '01-2026', 'pagamento' => 'pix']);
        foreach ($periodos as $periodo) {
            PeriodoOferecimento::create(['oferecimento_id' => $oferecimento->id, 'perfil' => $periodo, 'inicio' => now()->subDay(), 'fim' => now()->addDay()]);
        }

        $turma = Turma::create(['oferecimento_id' => $oferecimento->id]);
        foreach ($vagas as $perfil => $quantidade) {
            VagaTurma::create(['turma_id' => $turma->id, 'perfil' => $perfil, 'quantidade' => $quantidade]);
        }
        foreach ($perfisComTaxa ?? array_keys(config('cepe.perfil')) as $perfil) {
            TaxaTurma::create(['turma_id' => $turma->id, 'perfil' => $perfil, 'valor' => 10, 'periodo_inscricao' => 1]);
        }

        return $turma;
    }

    private function usuario(array $perfil = []): User
    {
        $user = User::factory()->create();
        Perfil::create(['user_id' => $user->id] + $perfil);

        return $user;
    }

    /** Usuário com carteira CEPEUSP vigente (paga) da categoria informada. */
    private function usuarioComCarteira(string $categoria = 'dependente_servidor'): User
    {
        $user = $this->usuario();
        \App\Models\Carteira::create([
            'solicitante_id' => $user->id, 'user_id' => $user->id, 'categoria' => $categoria,
            'nome' => $user->name, 'status' => 'ativa', 'numero' => '90631234',
            'validade_inicio' => now()->toDateString(), 'validade_fim' => now()->addYear()->toDateString(),
        ]);

        return $user;
    }

    private function elegiveis(Turma $turma, User $user): array
    {
        return array_keys(app(InscricaoService::class)->perfisElegiveis($turma->fresh(['oferecimento.periodos', 'vagas', 'taxas']), $user->fresh()));
    }

    public function test_tax_in_exige_vinculo_usp_real(): void
    {
        $turma = $this->turma();

        $this->assertSame([], $this->elegiveis($turma, $this->usuario()));
        $this->assertSame(['tax_in'], $this->elegiveis($turma, $this->usuario(['vinculos_usp' => ['ALUNOGR']])));
    }

    public function test_apenas_vinculos_da_lista_dao_direito_a_tax_in(): void
    {
        $turma = $this->turma();

        $this->assertSame([], $this->elegiveis($turma, $this->usuario(['vinculos_usp' => ['ESTAGIARIORH']])));
        $this->assertSame([], $this->elegiveis($turma, $this->usuario(['vinculos_usp' => ['ALUNOCEU', 'ALUNOCONVENIOINT']])));
        $this->assertSame(['tax_in'], $this->elegiveis($turma, $this->usuario(['vinculos_usp' => ['ESTAGIARIORH', 'DOCENTE']])));
    }

    public function test_perfil_sem_taxa_definida_nao_e_ofertado(): void
    {
        $semTaxa = $this->turma('semestral', ['usp'], ['tax_in' => 1], ['tax_in']);
        $dependente = $this->usuarioComCarteira('dependente_servidor');
        $conveniado = $this->usuarioComCarteira('conveniado');

        $this->assertSame([], $this->elegiveis($semTaxa, $dependente));
        $this->assertSame(['tax_con'], $this->elegiveis($semTaxa, $conveniado)); // herda a taxa de tax_in

        $comTaxa = $this->turma('semestral', ['usp'], ['tax_in' => 1], ['tax_in', 'tax_child']);
        $this->assertSame(['tax_child'], $this->elegiveis($comTaxa, $dependente));
    }

    public function test_oferecimento_gratuito_oferta_perfis_sem_taxa(): void
    {
        $turma = $this->turma('semestral', ['usp'], ['tax_in' => 1], []);
        $dependente = $this->usuarioComCarteira('dependente_servidor');

        $this->assertSame([], $this->elegiveis($turma, $dependente));

        $turma->oferecimento->update(['pagamento' => serialize(['gratuito'])]);
        $this->assertSame(['tax_child'], $this->elegiveis($turma, $dependente));
    }

    public function test_vinculo_apenas_alumni_nao_vira_tax_in(): void
    {
        $turma = $this->turma('voltausp', ['usp', 'alumni'], ['tax_in' => 1, 'tax_alumni' => 1]);

        $this->assertSame(['tax_alumni'], $this->elegiveis($turma, $this->usuario(['vinculos_usp' => ['ALUMNI']])));
        $this->assertEqualsCanonicalizing(['tax_in', 'tax_alumni'], $this->elegiveis($turma, $this->usuario(['vinculos_usp' => ['ALUMNI', 'SERVIDOR']])));
    }

    public function test_perfil_legado_sem_codigos_usa_o_rotulo_do_vinculo(): void
    {
        $turma = $this->turma();

        $this->assertSame(['tax_in'], $this->elegiveis($turma, $this->usuario(['vinculo_usp' => 'Servidor'])));
        $this->assertSame([], $this->elegiveis($turma, $this->usuario(['vinculo_usp' => 'Alumni'])));
    }

    public function test_dependente_e_conveniado_pela_categoria_da_carteira(): void
    {
        $turma = $this->turma();

        $this->assertSame(['tax_child'], $this->elegiveis($turma, $this->usuarioComCarteira('dependente_servidor')));
        $this->assertSame(['tax_child'], $this->elegiveis($turma, $this->usuarioComCarteira('dependente_aluno')));
        $this->assertSame(['tax_con'], $this->elegiveis($turma, $this->usuarioComCarteira('conveniado')));
        $this->assertSame([], $this->elegiveis($turma, $this->usuarioComCarteira('aposentado')));
    }

    public function test_child_e_con_nao_sao_ofertados_no_voltausp(): void
    {
        $turma = $this->turma('voltausp');

        $this->assertSame([], $this->elegiveis($turma, $this->usuarioComCarteira('conveniado')));
    }

    public function test_child_e_con_dividem_o_pool_de_vagas_de_tax_in(): void
    {
        $turma = $this->turma();
        $servico = app(InscricaoService::class);
        $dependente = $this->usuarioComCarteira('dependente_servidor');
        $conveniado = $this->usuarioComCarteira('conveniado');

        $this->assertSame(1, $servico->vagasDisponiveis($turma->fresh(['vagas', 'matriculas', 'oferecimento.periodos']), 'tax_con'));

        Matricula::create([
            'user_id' => $dependente->id, 'perfil' => 'tax_child', 'turma_id' => $turma->id,
            'periodo_inscricao' => 1, 'periodo_oferecimento' => '01-2026', 'status' => Matricula::MATRICULADA,
        ]);

        $turma = $turma->fresh(['vagas', 'matriculas', 'oferecimento.periodos']);
        $this->assertSame(0, $servico->vagasDisponiveis($turma, 'tax_con'));
        $this->assertSame(0, $servico->vagasDisponiveis($turma, 'tax_in'));
        $this->assertContains('tax_con', $this->elegiveis($turma, $conveniado));
    }
}
