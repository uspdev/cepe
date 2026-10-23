<?php

namespace App\Services;

use App\Models\Fila;
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
    public function vagasDisponiveis(Turma $turma, string $perfil, int $filaAFrente = 0): int
    {
        $turma->loadMissing(['vagas', 'matriculas']);
        $ativas = $turma->matriculas->where('status', '<>', Matricula::CANCELADA);

        if ($this->segundoPeriodoAberto($turma->oferecimento)) {
            return $turma->vagas->sum('quantidade') - $ativas->count() - $filaAFrente;
        }

        return $turma->vagasDoPerfil($perfil) - $ativas->where('perfil', $perfil)->count() - $filaAFrente;
    }

    /** Se o usuário pode se inscrever com o perfil informado. */
    public function elegivel(string $perfil, User $user): bool
    {
        $perfilUsuario = $user->perfilOuNovo();
        $credenciado = filled($perfilUsuario->numero_cepeusp) || filled($perfilUsuario->vinculo_usp);

        return match ($perfil) {
            'tax_ex' => true,
            'tax_ter' => ($perfilUsuario->idade ?? 0) >= 60,
            'tax_cepe' => filled($perfilUsuario->numero_cepeusp),
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

        // Elegibilidade (não disponibilidade): vagas esgotadas não bloqueiam — a fila cobre isso
        if ($this->perfisElegiveis($turma, $user) === []) {
            $erros[] = 'Não há vagas disponíveis para o seu perfil nesta turma.';
        }

        return $erros;
    }

    /**
     * Perfis que o usuário pode escolher: permitidos pelo período, com bucket
     * na turma e elegibilidade atendida — independente de haver vaga livre.
     *
     * @return array<string, array{label: string, valor: ?float}>
     */
    public function perfisElegiveis(Turma $turma, User $user): array
    {
        $periodoInscricao = $this->periodoInscricaoAtual($turma->oferecimento);
        $opcoes = [];

        foreach ($this->perfisPermitidos($turma) as $perfil) {
            if (! $this->elegivel($perfil, $user)) {
                continue;
            }

            $opcoes[$perfil] = [
                'label' => config('cepe.perfil.' . $perfil, $perfil),
                'valor' => $turma->taxaDoPerfil($perfil, $periodoInscricao)?->valor,
            ];
        }

        return $opcoes;
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

    /**
     * Entrada ativa do usuário na fila da turma, ou null.
     */
    public function filaAtiva(Turma $turma, User $user): ?Fila
    {
        return Fila::ativasDaTurma($turma)->where('user_id', $user->id)->latest('posicao')->first();
    }

    /**
     * Insere o usuário na fila da turma (idempotente) e registra heartbeat —
     * todo usuário que inicia a inscrição é inserido, para o levantamento do
     * número de interessados na modalidade. O perfil é opcional na entrada e
     * pode ser escolhido/atualizado depois (mantém a posição).
     */
    public function entrarNaFila(Turma $turma, User $user, ?string $perfil = null): Fila
    {
        $elegiveis = $this->perfisElegiveis($turma, $user);

        // Perfil único elegível: não exige escolha — registra direto na fila
        if ($perfil === null && count($elegiveis) === 1) {
            $perfil = array_key_first($elegiveis);
        }

        if ($perfil !== null && ! array_key_exists($perfil, $elegiveis)) {
            throw ValidationException::withMessages([
                'perfil' => 'Escolha um perfil de inscrição disponível.',
            ]);
        }

        if ($fila = $this->filaAtiva($turma, $user)) {
            $fila->fill(['perfil' => $perfil ?? $fila->perfil])->save();
            $fila->touch();

            return $fila;
        }

        return DB::transaction(function () use ($turma, $user, $perfil) {
            // posição cresce sem reutilizar números, para fins de levantamento
            $posicao = (int) Fila::where('turma_id', $turma->id)->lockForUpdate()->max('posicao') + 1;

            return Fila::create([
                'user_id' => $user->id,
                'turma_id' => $turma->id,
                'posicao' => $posicao,
                'perfil' => $perfil,
                'status' => true,
            ]);
        });
    }

    /**
     * Quantas pessoas ainda ativas na fila disputam o bucket do perfil.
     * Com $fila, conta só quem está à frente (posicao menor); sem $fila,
     * conta todos os filados do bucket. Entradas sem perfil escolhido contam
     * em todos os buckets. No período 'segundo' o pool é compartilhado e
     * todos à frente contam. Entradas sem heartbeat recente (fila_ttl) não
     * bloqueiam quem está atrás.
     */
    public function filaAFrente(Turma $turma, ?Fila $fila, ?string $perfil = null): int
    {
        $query = Fila::ativasDaTurma($turma)
            ->where('updated_at', '>=', now()->subSeconds(config('cepe.fila_ttl')));

        if ($fila) {
            $query->where('posicao', '<', $fila->posicao);
        }

        if (! $this->segundoPeriodoAberto($turma->oferecimento)) {
            $perfil ??= $fila?->perfil;
            $query->where(fn ($q) => $q->where('perfil', $perfil)->orWhereNull('perfil'));
        }

        return $query->count();
    }

    /**
     * Perfis elegíveis que ainda têm vaga livre para o usuário: desconta
     * matriculados e os filados à frente dele em cada bucket.
     *
     * @return array<string, array{label: string, valor: ?float, vagas: int}>
     */
    public function perfisLivres(Turma $turma, User $user, ?Fila $fila = null): array
    {
        $fila ??= $this->filaAtiva($turma, $user);
        $periodoInscricao = $this->periodoInscricaoAtual($turma->oferecimento);
        $opcoes = [];

        foreach (array_keys($this->perfisElegiveis($turma, $user)) as $perfil) {
            if ($fila?->perfil && $perfil !== $fila->perfil) {
                continue; // quem escolheu perfil na fila só disputa aquele bucket
            }

            $vagas = $this->vagasDisponiveis($turma, $perfil, $this->filaAFrente($turma, $fila, $perfil));
            if ($vagas > 0) {
                $opcoes[$perfil] = [
                    'label' => config('cepe.perfil.' . $perfil, $perfil),
                    'valor' => $turma->taxaDoPerfil($perfil, $periodoInscricao)?->valor,
                    'vagas' => $vagas,
                ];
            }
        }

        return $opcoes;
    }

    /**
     * É a vez do usuário quando existe perfil com vaga livre descontando
     * matriculados e quem está à frente na fila. Sem entrada na fila, não
     * pode ser a vez (é preciso registrar o interesse primeiro).
     */
    public function isVez(Turma $turma, User $user, ?Fila $fila = null): bool
    {
        $fila ??= $this->filaAtiva($turma, $user);

        return (bool) $fila && $this->perfisLivres($turma, $user, $fila) !== [];
    }

    /**
     * Menor posição ainda ativa e com heartbeat fresco (o "atendimento" atual).
     */
    public function filaHead(Turma $turma): int
    {
        return (int) Fila::ativasDaTurma($turma)
            ->where('updated_at', '>=', now()->subSeconds(config('cepe.fila_ttl')))
            ->min('posicao') ?: 1;
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
