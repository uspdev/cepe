<?php

namespace App\Http\Controllers;

use App\Exceptions\PagamentoIntegracaoException;
use App\Http\Requests\InscricaoRequest;
use App\Models\Matricula;
use App\Models\Turma;
use App\Services\CobrancaService;
use App\Services\InscricaoService;
use Illuminate\Validation\ValidationException;

class InscricaoController extends Controller
{
    public function __construct(private InscricaoService $inscricoes) {}

    public function create(Turma $turma)
    {
        $turma->loadMissing(['oferecimento.atividade', 'oferecimento.periodos', 'vagas', 'taxas', 'professor']);

        $user = auth()->user();
        $erros = $this->inscricoes->validar($turma, $user);

        // Todo usuário apto entra na fila ao abrir a inscrição — registra o
        // interesse na modalidade e estabelece posição caso falte vaga.
        $fila = $erros ? null : $this->inscricoes->entrarNaFila($turma, $user);

        return view('inscricoes.create', [
            'turma' => $turma,
            'erros' => $erros,
            'perfisElegiveis' => $this->inscricoes->perfisComPagamentoDisponivel($turma, $user),
            'perfisLivres' => $this->inscricoes->perfisLivres($turma, $user, $fila),
            'minhaFila' => $fila,
            'posicao' => $fila?->posicao,
            'head' => $this->inscricoes->filaHead($turma),
            'isHead' => (bool) $fila && $this->inscricoes->isVez($turma, $user, $fila),
        ]);
    }

    public function store(InscricaoRequest $request, Turma $turma, CobrancaService $cobrancas)
    {
        $user = $request->user();
        $fila = $this->inscricoes->filaAtiva($turma, $user) ?? $this->inscricoes->entrarNaFila($turma, $user);

        if (! $this->inscricoes->isVez($turma, $user, $fila)) {
            throw ValidationException::withMessages([
                'inscricao' => $fila->perfil
                    ? 'Ainda não é a sua vez na fila de inscrição. Aguarde na página de inscrição.'
                    : 'As vagas estão esgotadas para o seu perfil. Entre na fila de inscrição pela página da turma.',
            ]);
        }

        // Na fila com perfil escolhido, usa-o; senão, valida o do formulário contra o que está livre
        $perfil = $fila->perfil ?? $request->perfil;
        if (! array_key_exists($perfil, $this->inscricoes->perfisLivres($turma, $user, $fila))) {
            throw ValidationException::withMessages(['perfil' => 'Perfil indisponível para inscrição.']);
        }

        $matricula = $this->inscricoes->inscrever($turma, $user, $perfil, [
            'image_use_authorization' => $request->image_use_authorization,
            'regulamento_aceito' => 'sim',
            'regulamento_ip' => $request->ip(),
        ], $request->metodo_pagamento);

        if ($matricula->status === Matricula::AGUARDANDO_PAGAMENTO) {
            try {
                $cobrancas->emitirPrimeira($matricula, $request->metodo_pagamento);
            } catch (PagamentoIntegracaoException $e) {
                if (! $e->resultadoIndeterminado) {
                    $cobrancas->cancelarMatriculaSemPagamento($matricula);
                    throw ValidationException::withMessages([
                        'metodo_pagamento' => 'Não foi possível emitir a cobrança. Tente novamente; sua vaga não foi confirmada.',
                    ]);
                }

                $fila->update(['status' => false, 'perfil' => $perfil]);

                return redirect('/minhas-inscricoes')
                    ->with('alert-warning', 'A emissão do pagamento está sendo verificada. Consulte Minhas Inscrições antes de tentar novamente.');
            }
        }

        $fila->update(['status' => false, 'perfil' => $perfil]);

        return redirect('/minhas-inscricoes')
            ->with(
                'alert-success',
                $matricula->status === Matricula::AGUARDANDO_PAGAMENTO
                    ? ($request->metodo_pagamento === 'pix'
                        ? 'Pix gerado. Consulte Minhas Inscrições para pagar.'
                        : 'Boleto emitido. Consulte Minhas Inscrições para pagar.')
                    : 'Inscrição realizada com sucesso!',
            );
    }
}
