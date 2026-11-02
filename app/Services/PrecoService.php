<?php

namespace App\Services;

use App\Models\Matricula;
use App\Models\Perfil;
use App\Models\Turma;
use App\Models\User;

class PrecoService
{
    /**
     * Calcula a taxa do usuário para o perfil na turma (valores em reais inteiros).
     * Ordem: taxa base, PAPFE em cursos, taxa por vínculo, desconto de 2º curso, crédito.
     *
     * @return array{base: ?int, vinculo: ?string, desconto_vinculo: int, desconto_curso: int, credito_usado: int, desconto: int, final: ?int}
     */
    public function calcular(Turma $turma, User $user, string $perfil, int $periodoInscricao): array
    {
        $turma->loadMissing(['taxas', 'oferecimento.atividade']);
        $usuario = $user->perfilOuNovo();

        $resultado = ['base' => null, 'vinculo' => null, 'desconto_vinculo' => 0, 'desconto_curso' => 0, 'credito_usado' => 0, 'desconto' => 0, 'final' => null];

        $base = $turma->taxaDoPerfil($perfil, $periodoInscricao)?->valor;
        $gratuito = $turma->oferecimento->gratuito();
        if ($base === null && ! $gratuito) {
            return $resultado;
        }

        $base = (int) $base;
        $resultado['base'] = $base;
        $resultado['final'] = $base;

        $curso = in_array($turma->oferecimento->atividade->tipo, config('cepe.grupos_catalogo.cursos'));

        if ($gratuito || ($curso && $perfil === 'tax_papfe')) {
            return ['final' => 0, 'desconto' => $base] + $resultado;
        }

        $tipo = $turma->oferecimento->atividade->tipo;
        [$valor, $resultado['vinculo']] = in_array($tipo, config('cepe.taxa_vinculo.tipos', []))
            ? $this->aplicarVinculo($turma, $usuario, $perfil, $periodoInscricao, $base)
            : [$base, null];
        $resultado['desconto_vinculo'] = $base - $valor;

        $antes = $valor;
        if ($curso && config('cepe.desconto_segundo_curso.percentuais.' . $perfil) && $this->temCursoNoPeriodo($user, $turma)) {
            $valor = $this->aplicarSegundoCurso($valor, $perfil, $usuario);
        }
        $resultado['desconto_curso'] = $antes - $valor;

        // Regra do legado: crédito que cobre a taxa zera o pagamento; senão cobra-se no mínimo
        // a taxa mínima, e a diferença vira crédito (credito_usado negativo) quando a taxa é menor que o mínimo.
        if ($valor > 0) {
            $credito = (int) $usuario->credito;
            $pago = $credito >= $valor ? 0 : max($valor - $credito, (int) config('cepe.taxa_minima'));
            $resultado['credito_usado'] = $valor - $pago;
            $valor = $pago;
        }

        $resultado['final'] = $valor;
        $resultado['desconto'] = $base - $valor - $resultado['credito_usado'];

        return $resultado;
    }

    /** Menor taxa entre os vínculos do usuário configurados na turma. */
    private function aplicarVinculo(Turma $turma, Perfil $usuario, string $perfil, int $periodo, int $valor): array
    {
        $escolhido = null;

        if (! in_array($perfil, config('cepe.taxa_vinculo.perfis', []))) {
            return [$valor, $escolhido];
        }

        foreach (array_keys(config('cepe.taxa_vinculo.vinculos', [])) as $codigo) {
            $taxa = $usuario->temVinculo($codigo)
                ? $turma->taxas->where('perfil', $perfil)->where('periodo_inscricao', $periodo)->where('vinculo', $codigo)->first()
                : null;

            if ($taxa && (int) $taxa->valor < $valor) {
                $valor = (int) $taxa->valor;
                $escolhido = $codigo;
            }
        }

        return [$valor, $escolhido];
    }

    private function temCursoNoPeriodo(User $user, Turma $turma): bool
    {
        return Matricula::ativas()
            ->where('user_id', $user->id)
            ->where('periodo_oferecimento', $turma->oferecimento->periodo)
            ->whereHas('turma.oferecimento.atividade', fn ($q) => $q->whereIn('tipo', config('cepe.grupos_catalogo.cursos')))
            ->exists();
    }

    private function aplicarSegundoCurso(int $valor, string $perfil, Perfil $usuario): int
    {
        $percentual = config('cepe.desconto_segundo_curso.percentuais.' . $perfil);

        if (! $percentual || ($perfil === 'tax_ex' && $usuario->categoriaCepeusp() === 'conveniado')) {
            return $valor;
        }

        $desconto = $valor * $percentual;
        $minimo = (int) config('cepe.desconto_segundo_curso.minimo');
        if ($desconto != 0 && $desconto < $minimo) {
            $desconto = $minimo;
        }

        return max(0, (int) round($valor - $desconto));
    }
}
