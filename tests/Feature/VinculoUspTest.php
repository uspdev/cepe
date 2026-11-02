<?php

namespace Tests\Feature;

use App\Models\Perfil;
use App\Models\User;
use App\Support\VinculoUsp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SocialiteProviders\Manager\OAuth1\User as SocialiteUser;
use Tests\TestCase;
use Uspdev\SenhaunicaSocialite\Events\SenhaunicaUsuarioLogado;

class VinculoUspTest extends TestCase
{
    use RefreshDatabase;

    private function vinculos(): array
    {
        return [
            ['tipoVinculo' => 'SERVIDOR', 'tipoFuncao' => 'Docente', 'siglaUnidade' => 'EEFE', 'nomeUnidade' => 'Escola de Educação Física e Esporte'],
            ['tipoVinculo' => 'ALUNOPOS', 'tipoFuncao' => null, 'siglaUnidade' => 'EEFE', 'nomeUnidade' => 'Escola de Educação Física e Esporte'],
            ['tipoVinculo' => 'ALUNOGR', 'siglaUnidade' => 'IME', 'nomeUnidade' => 'Instituto de Matemática e Estatística'],
            ['tipoVinculo' => 'SERVIDOR', 'tipvinext' => 'Servidor Designado', 'siglaUnidade' => 'XXX', 'nomeUnidade' => 'Ignorada'],
        ];
    }

    private function logar(User $user, array $vinculos): void
    {
        $socialite = (new SocialiteUser)->map(['codpes' => $user->codpes, 'vinculo' => $vinculos]);
        event(new SenhaunicaUsuarioLogado($user, $socialite, 'senhaunica'));
    }

    public function test_resumo_dos_vinculos(): void
    {
        $r = VinculoUsp::resumir($this->vinculos());

        $this->assertSame('Docente, Aluno de Pós-Graduação, Aluno de Graduação', $r['vinculo']);
        $this->assertSame('EEFE - Escola de Educação Física e Esporte; IME - Instituto de Matemática e Estatística', $r['unidade']);
        $this->assertSame(['vinculo' => null, 'unidade' => null], VinculoUsp::resumir([]));
    }

    public function test_login_cria_e_atualiza_perfil(): void
    {
        $user = User::factory()->create(['codpes' => 123]);

        $this->logar($user, $this->vinculos());
        $perfil = $user->fresh()->perfil;
        $this->assertStringContainsString('Docente', $perfil->vinculo_usp);
        $this->assertStringContainsString('IME', $perfil->unidade_usp);

        $this->logar($user, [['tipoVinculo' => 'ALUNOGR', 'siglaUnidade' => 'FEA', 'nomeUnidade' => 'Faculdade de Economia']]);
        $perfil = $user->fresh()->perfil;
        $this->assertSame('Aluno de Graduação', $perfil->vinculo_usp);
        $this->assertSame('FEA - Faculdade de Economia', $perfil->unidade_usp);
        $this->assertSame(1, Perfil::count());
    }

    public function test_preserva_dados_do_perfil_e_revalida_vinculo_vazio(): void
    {
        $user = User::factory()->create(['codpes' => 123]);
        Perfil::create(['user_id' => $user->id, 'cidade' => 'São Paulo', 'vinculo_usp' => 'Antigo', 'vinculos_usp' => ['ALUNOGR']]);

        $this->logar($user, []);
        $perfil = $user->fresh()->perfil;
        $this->assertNull($perfil->vinculo_usp);
        $this->assertSame([], $perfil->vinculos_usp);
        $this->assertSame('São Paulo', $perfil->cidade);

        $this->logar($user, $this->vinculos());
        $perfil = $user->fresh()->perfil;
        $this->assertSame('São Paulo', $perfil->cidade);
        $this->assertStringContainsString('Docente', $perfil->vinculo_usp);
        $this->assertEqualsCanonicalizing(['SERVIDOR', 'ALUNOPOS', 'ALUNOGR'], $perfil->vinculos_usp);
    }

    public function test_vinculo_definido_por_admin_nao_e_apagado_no_login(): void
    {
        $user = User::factory()->create(['codpes' => 123]);
        Perfil::create(['user_id' => $user->id, 'vinculo_usp' => 'Docente aposentado', 'vinculo_manual' => true, 'vinculos_usp' => ['ALUNOGR', 'ALUMNI']]);

        // sem vínculo na Senha Única: o rótulo manual fica, mas os códigos (descontos/alumni) são zerados
        $this->logar($user, []);
        $perfil = $user->fresh()->perfil;
        $this->assertSame('Docente aposentado', $perfil->vinculo_usp);
        $this->assertSame([], $perfil->vinculos_usp);
        $this->assertFalse($perfil->temVinculo('ALUMNI'));

        $this->logar($user, [['tipoVinculo' => 'ALUNOGR']]);
        $perfil = $user->fresh()->perfil;
        $this->assertSame('Docente aposentado', $perfil->vinculo_usp);
        $this->assertSame(['ALUNOGR'], $perfil->vinculos_usp);
    }

    public function test_codigos_dos_vinculos(): void
    {
        $this->assertEqualsCanonicalizing(['SERVIDOR', 'ALUNOPOS', 'ALUNOGR'], VinculoUsp::codigos($this->vinculos()));
        $this->assertSame(['ALUMNI'], VinculoUsp::codigos([['tipoVinculo' => 'alumni']]));
        $this->assertSame('Alumni', VinculoUsp::resumir([['tipoVinculo' => 'ALUMNI']])['vinculo']);
    }
}
