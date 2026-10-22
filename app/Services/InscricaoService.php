<?php

namespace App\Services;

use App\Models\Matricula;
use App\Models\Oferecimento;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InscricaoService
{
    /** Chaves dos períodos abertos no oferecimento agora (exceto 'curso'). */
    public function periodosAbertos(Oferecimento $oferecimento): array
    {
        return $oferecimento->periodosAbertos()->pluck('perfil')->all();
    }

    public function segundoPeriodoAberto(Oferecimento $oferecimento): bool
    {
        return in_array('segundo', $this->periodosAbertos($oferecimento));
    }

    /** 1 ou 2: gravado na matrícula; taxas do período 'segundo' usam periodo_inscricao 2. */
    public function periodoInscricaoAtual(Oferecimento $oferecimento): int
    {
        return $this->segundoPeriodoAberto($oferecimento) ? 2 : 1;
    }

    /** Perfis habilitados pelos períodos abertos, restritos aos buckets de vagas da turma. */
    public function perfisPermitidos(Turma $turma): array
    {
        $permitidos = collect($this->periodosAbertos($turma->oferecimento))
            ->flatMap(fn ($p) => config('cepe.periodo_perfis.' . $p, []))
            ->unique();

        $configurados = $turma->vagas->pluck('perfil')->unique();

        return $permitidos->intersect($configurados)->values()->all();
    }

    /**
     * Vagas disponíveis para um perfil. Nos períodos normais conta o bucket do
     * perfil; no período 'segundo' o pool é compartilhado entre todos os perfis.
     */
    public function vagasDisponiveis(Turma $turma, string $perfil): int
    {
        $turma->loadMissing(['vagas', 'matriculas']);
        $ativas = $turma->matriculas->where('status', '<>', Matricula::CANCELADA);

        if ($this->segundoPeriodoAberto($turma->oferecimento)) {
            return $turma->vagas->sum('quantidade') - $ativas->count();
        }

        return $turma->vagasDoPerfil($perfil) - $ativas->where('perfil', $perfil)->count();
    }

    /** Se o usuário pode se inscrever com o perfil informado. */
    public function elegivel(string $perfil, User $user): bool
    {
        $perfilUsuario = $user->perfilOuNovo();
        $credenciado = filled($perfilUsuario->numero_cepeusp) || filled($perfilUsuario->vinculo_usp);

        return match ($perfil) {
            'tax_ex' => true,
            'tax_ter' => ($perfilUsuario->idade ?? 0) >= 60,
            'tax_papfe' => filled($perfilUsuario->numero_cepeusp),
            default => $credenciado,
        };
    }

    /**
     * Perfis que o usuário pode escolher agora: permitidos pelo período,
     * com vaga disponível, taxa definida e elegibilidade atendida.
     *
     * @return array<string, array{label: string, valor: ?float, vagas: int}>
     */
    public function perfisDisponiveis(Turma $turma, User $user): array
    {
        $periodoInscricao = $this->periodoInscricaoAtual($turma->oferecimento);
        $opcoes = [];

        foreach ($this->perfisPermitidos($turma) as $perfil) {
            if (! $this->elegivel($perfil, $user)) {
                continue;
            }

            $vagas = $this->vagasDisponiveis($turma, $perfil);
            if ($vagas <= 0) {
                continue;
            }

            $opcoes[$perfil] = [
                'label' => config('cepe.perfil.' . $perfil, $perfil),
                'valor' => $turma->taxaDoPerfil($perfil, $periodoInscricao)?->valor,
                'vagas' => $vagas,
            ];
        }

        return $opcoes;
    }

    /**
     * Cadeia de bloqueios da inscrição (ordem do fluxo legado).
     *
     * @return string[] mensagens de bloqueio; vazio = pode se inscrever
     */
    public function validar(Turma $turma, User $user): array
    {
        $turma->loadMissing(['oferecimento.atividade', 'oferecimento.periodos', 'vagas', 'matriculas']);
        $oferecimento = $turma->oferecimento;
        $atividade = $oferecimento->atividade;
        $perfil = $user->perfilOuNovo();
        $erros = [];

        if ($this->periodosAbertos($oferecimento) === []) {
            $erros[] = 'O período de inscrições está fechado para esta atividade.';

            return $erros;
        }

        if (! $perfil->completo()) {
            $erros[] = 'Seu perfil está incompleto (' . implode(', ', $perfil->camposFaltantes()) . '). Complete-o em "Meu perfil" antes de se inscrever.';
        }

        $ativas = Matricula::ativas()
            ->where('user_id', $user->id)
            ->where('periodo_oferecimento', $oferecimento->periodo);

        $tipoCurso = in_array($atividade->tipo, config('cepe.grupos_catalogo.cursos'));
        $tipoEvento = $atividade->tipo === 'eventos';

        if ($tipoEvento) {
            $jaInscrito = (clone $ativas)->where('turma_id', $turma->id)->exists();
        } else {
            $jaInscrito = (clone $ativas)
                ->whereHas('turma.oferecimento', fn ($q) => $q->where('atividade_id', $atividade->id))
                ->exists();
        }

        if ($jaInscrito) {
            $erros[] = $tipoEvento
                ? 'Você já está inscrito nesta turma.'
                : 'Você já está inscrito nesta modalidade.';
        }

        if ($tipoCurso) {
            $totalCursos = (clone $ativas)
                ->whereHas('turma.oferecimento.atividade', fn ($q) => $q->whereIn('tipo', config('cepe.grupos_catalogo.cursos')))
                ->count();

            $limite = config('cepe.limite_cursos');
            if ($totalCursos >= $limite['total']) {
                $erros[] = "O CEPEUSP somente aceita matrícula em {$limite['total']} modalidades de cursos por usuário!";
            } elseif ($atividade->tipo === 'semestral' && $oferecimento->limitar_matricula
                && $totalCursos >= $limite['primeiro'] && ! $this->segundoPeriodoAberto($oferecimento)) {
                $erros[] = "O CEPEUSP somente aceita matrícula em {$limite['primeiro']} modalidade de curso por período de inscrição!";
            }
        }

        if ($perfil->idade !== null) {
            if ($turma->idade_minima !== null && $perfil->idade < $turma->idade_minima
                || $turma->idade_maxima !== null && $perfil->idade > $turma->idade_maxima) {
                $faixa = $turma->idade_maxima === null || $turma->idade_maxima > 100
                    ? "a partir de {$turma->idade_minima} anos"
                    : "entre {$turma->idade_minima} e {$turma->idade_maxima} anos";
                $erros[] = "Esta atividade é destinada a pessoas {$faixa}; verifique a data de nascimento do seu perfil.";
            }
        }

        if (! empty($turma->sexo) && ! in_array($perfil->sexo, $turma->sexo)) {
            $erros[] = 'Esta turma possui restrição de sexo e, segundo os dados do seu perfil, você não se enquadra na condição exigida.';
        }

        if ($oferecimento->atestado_medico && ! $this->temAtestadoValido($user, $perfil)) {
            $erros[] = 'Esta atividade exige atestado médico aprovado (ou PAR-Q dentro da faixa etária). Envie o documento em "Meus atestados".';
        }

        if ($oferecimento->exame_dermatologico && ! $this->temDocumentoValido($user, ['exame_dermatologico'])) {
            $erros[] = 'Esta atividade exige exame dermatológico aprovado. Envie o documento em "Meus atestados".';
        }

        if ($this->perfisDisponiveis($turma, $user) === []) {
            $erros[] = 'Não há vagas disponíveis para o seu perfil nesta turma.';
        }

        return $erros;
    }

    /**
     * Efetiva a inscrição. Revalida dentro de transação com lock nas vagas da
     * turma para serializar inscrições concorrentes.
     */
    public function inscrever(Turma $turma, User $user, string $perfil, array $metadados = []): Matricula
    {
        return DB::transaction(function () use ($turma, $user, $perfil, $metadados) {
            $turma->vagas()->lockForUpdate()->get();

            $erros = $this->validar($turma, $user);
            if ($erros) {
                throw ValidationException::withMessages(['inscricao' => $erros]);
            }

            $disponiveis = $this->perfisDisponiveis($turma->fresh(['vagas', 'matriculas', 'oferecimento.periodos']), $user);
            if (! array_key_exists($perfil, $disponiveis)) {
                throw ValidationException::withMessages(['perfil' => 'Perfil indisponível para inscrição.']);
            }

            $oferecimento = $turma->oferecimento;
            $matricula = Matricula::create([
                'user_id' => $user->id,
                'perfil' => $perfil,
                'turma_id' => $turma->id,
                'periodo_inscricao' => $this->periodoInscricaoAtual($oferecimento),
                'periodo_oferecimento' => $oferecimento->periodo,
                'status' => Matricula::MATRICULADA,
            ]);

            foreach ($metadados as $chave => $valor) {
                if ($valor !== null && $valor !== '') {
                    $matricula->metadados()->create(['chave' => $chave, 'valor' => $valor]);
                }
            }

            return $matricula;
        });
    }

    /** Atestado médico aprovado e vigente; PAR-Q substitui dentro da faixa etária. */
    private function temAtestadoValido(User $user, $perfil): bool
    {
        $tipos = ['atestado_medico'];
        if ($perfil->parqPermitido()) {
            $tipos[] = 'parq';
        }

        return $this->temDocumentoValido($user, $tipos);
    }

    private function temDocumentoValido(User $user, array $tipos): bool
    {
        return $user->atestados()
            ->whereIn('tipo', $tipos)
            ->get()
            ->contains(fn ($a) => $a->status_efetivo === 'aprovado');
    }
}
