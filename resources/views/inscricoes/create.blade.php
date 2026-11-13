@extends('layout')

@section('content')
@php
    $oferecimento = $turma->oferecimento;
    $atividade = $oferecimento->atividade;
@endphp
<div class="container py-4">
    <div class="card shadow-sm col-md-8 mx-auto">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h1 class="h4 mb-0 text-primary">Inscrição — {{ $atividade->nome }}</h1>
            <a href="{{ route('catalogo.show', $atividade) }}" class="btn btn-outline-secondary btn-sm">&larr; Voltar</a>
        </div>

        <div class="card-body">
            <div class="mb-4">
                <label class="form-label text-muted fw-bold font-weight-bold">Turma</label>
                <ul class="list-unstyled small mb-0">
                    @if($turma->professor)<li><strong>Professor:</strong> {{ $turma->professor->name }}</li>@endif
                    @if($turma->dias_semana_formatados)<li><strong>Dias:</strong> {{ $turma->dias_semana_formatados }}</li>@endif
                    <li><strong>Horário:</strong> {{ $turma->horario_formatado }}</li>
                    @if($turma->local)<li><strong>Local:</strong> {{ $turma->local }}</li>@endif
                    <li><strong>Período:</strong> {{ $oferecimento->periodo }}</li>
                </ul>
            </div>

            @if($turma->declaracao)
                <div class="alert alert-secondary small">{{ $turma->declaracao }}</div>
            @endif

            @if($erros)
                @foreach($erros as $erro)
                    <div class="alert alert-warning">{{ $erro }}</div>
                @endforeach
            @else
                <div id="caixa-fila"
                     data-status-url="{{ route('filas.status', $turma) }}"
                     data-fila-state="{{ $isHead ? 'vez' : ($minhaFila?->perfil ? 'aguardando' : 'fora') }}">

                    {{-- Vagas esgotadas e perfil ainda não escolhido: entrada na fila --}}
                    <div id="fila-estado-fora" class="card mb-4 border-secondary shadow-sm {{ ($minhaFila && !$isHead && !$minhaFila->perfil) ? '' : 'd-none' }}">
                        <div class="card-header bg-light py-2">
                            <span class="font-weight-bold fw-bold text-secondary">
                                <i class="fas fa-users mr-1"></i> Fila de Inscrição
                            </span>
                        </div>
                        <div class="card-body p-4">
                            <p class="text-muted">
                                As vagas estão esgotadas no momento. Escolha o perfil e entre na fila de espera —
                                você será chamado quando surgir uma vaga.
                            </p>
                            <form method="POST" action="{{ route('filas.entrar', $turma) }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-bold font-weight-bold">Perfil de inscrição</label>
                                    @if(count($perfisElegiveis) > 1)
                                        @foreach($perfisElegiveis as $perfil => $dados)
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="perfil" id="fila_perfil_{{ $perfil }}" value="{{ $perfil }}" {{ old('perfil') === $perfil ? 'checked' : '' }} required>
                                                <label class="form-check-label" for="fila_perfil_{{ $perfil }}">
                                                    {{ $dados['label'] }}
                                                    @include('inscricoes.partials.preco', ['dados' => $dados])
                                                </label>
                                            </div>
                                        @endforeach
                                    @else
                                        @php
                                            $dados = reset($perfisElegiveis);
                                        @endphp
                                        <input type="hidden" name="perfil" value="{{ key($perfisElegiveis) }}">
                                        <p class="mb-0">
                                            {{ $dados['label'] }}
                                            @include('inscricoes.partials.preco', ['dados' => $dados])
                                        </p>
                                    @endif
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg px-4 shadow-sm">
                                    Entrar na fila
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Aguardando: na fila com perfil escolhido, sem vaga livre no bucket --}}
                    <div id="fila-estado-aguardando" class="card mb-4 border-info shadow-sm {{ ($minhaFila && $minhaFila->perfil && !$isHead) ? '' : 'd-none' }}">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                            <span class="font-weight-bold fw-bold text-secondary">
                                <i class="fas fa-users mr-1"></i> Fila de Inscrição
                            </span>
                            <span id="fila-badge" class="badge badge-primary px-2 py-1">Aguardando</span>
                        </div>
                        <div class="card-body text-center p-4">
                            <div class="alert alert-info border-info mb-3 p-3">
                                <div class="mb-2">
                                    <div class="spinner-border spinner-border-sm text-info mr-1" role="status">
                                        <span class="sr-only">Aguardando...</span>
                                    </div>
                                    <strong>Vagas esgotadas — fila de espera em andamento</strong>
                                </div>
                                <div class="h5 mb-0" id="texto-fila">
                                    Você está na posição <span id="fila-posicao" class="badge badge-primary px-2 py-1">{{ $posicao }}</span> e o atendimento está no usuário de número <span id="fila-head" class="badge badge-secondary px-2 py-1">{{ $head }}</span>. Aguarde.
                                </div>
                            </div>
                            <small class="text-muted">
                                Perfil escolhido: <strong>{{ config('cepe.perfil.' . $minhaFila?->perfil, $minhaFila?->perfil) }}</strong>.
                                Aguarde nesta página. O formulário de inscrição será liberado automaticamente quando surgir uma vaga para você.
                            </small>
                        </div>
                    </div>

                    {{-- É a vez — formulário liberado --}}
                    <div id="fila-estado-vez" class="alert alert-success border-success mb-4 p-3 {{ ($minhaFila && $isHead) ? '' : 'd-none' }}">
                        <h5 class="alert-heading font-weight-bold fw-bold mb-1">
                            É a sua vez de fazer a inscrição!
                        </h5>
                        <p class="mb-0">
                            Você é o usuário de número <strong><span id="fila-posicao-vez">{{ $posicao }}</span></strong> na fila. Por favor, preencha o formulário abaixo para confirmar sua inscrição.
                        </p>
                    </div>
                </div>

                {{-- Formulário de confirmação de inscrição (habilitado quando for a vez do usuário) --}}
                <div id="secao-formulario-inscricao" class="{{ $isHead ? '' : 'd-none' }}">
                    <form method="POST" action="{{ route('inscricoes.store', $turma) }}">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label fw-bold font-weight-bold">Perfil de inscrição</label>
                            @if($minhaFila?->perfil)
                                {{-- Perfil travado no escolhido ao entrar na fila --}}
                                <input type="hidden" name="perfil" value="{{ $minhaFila->perfil }}" data-valor="{{ $perfisElegiveis[$minhaFila->perfil]['valor'] ?? 0 }}">
                                <p class="mb-0">
                                    {{ config('cepe.perfil.' . $minhaFila->perfil, $minhaFila->perfil) }}
                                    @isset($perfisElegiveis[$minhaFila->perfil])
                                        @include('inscricoes.partials.preco', ['dados' => $perfisElegiveis[$minhaFila->perfil]])
                                    @endisset
                                </p>
                            @elseif(count($perfisLivres) === 1)
                                {{-- Único perfil disponível: exibe fixo, sem escolha --}}
                                @php
                                    $dados = reset($perfisLivres);
                                @endphp
                                <input type="hidden" name="perfil" value="{{ key($perfisLivres) }}" data-valor="{{ $dados['valor'] ?? 0 }}">
                                <p class="mb-0">
                                    {{ $dados['label'] }}
                                    @include('inscricoes.partials.preco', ['dados' => $dados])
                                </p>
                            @else
                                @foreach($perfisLivres as $perfil => $dados)
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="perfil" id="perfil_{{ $perfil }}" value="{{ $perfil }}" data-valor="{{ $dados['valor'] ?? 0 }}" {{ old('perfil') === $perfil ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="perfil_{{ $perfil }}">
                                            {{ $dados['label'] }}
                                            @include('inscricoes.partials.preco', ['dados' => $dados])
                                            <span class="text-muted small">({{ $dados['vagas'] }} {{ $dados['vagas'] > 1 ? 'vagas' : 'vaga' }})</span>
                                        </label>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        @php
                            $formasPagamento = $oferecimento->formas_pagamento;
                            $temPerfilPago = collect($perfisLivres)->contains(fn ($dados) => ($dados['valor'] ?? 0) > 0);
                            $metodosPagamento = array_values(array_intersect(['pix', 'boleto'], $formasPagamento));
                        @endphp
                        @if($metodosPagamento !== [] && $temPerfilPago)
                            <div id="metodos-pagamento" class="mb-4">
                                <label class="form-label fw-bold font-weight-bold">Forma de pagamento</label>
                                @foreach($metodosPagamento as $metodo)
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="metodo_pagamento" id="metodo_{{ $metodo }}" value="{{ $metodo }}" {{ old('metodo_pagamento') === $metodo || (!old('metodo_pagamento') && count($metodosPagamento) === 1) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="metodo_{{ $metodo }}">{{ $metodo === 'pix' ? 'Pix' : 'Boleto bancário' }}</label>
                                    </div>
                                @endforeach
                                @if(in_array('pix', $metodosPagamento, true))
                                    <div class="small text-muted mt-2">O Pix expira em até 3 horas e a confirmação é automática.</div>
                                @endif
                                @if(in_array('boleto', $metodosPagamento, true) && in_array($atividade->tipo, config('cepe.grupos_catalogo.cursos'), true) && collect($perfisLivres)->contains(fn ($dados) => ($dados['valor'] ?? 0) >= config('cepe.pagamentos.parcelamento_cursos.valor_minimo')))
                                    <div class="small text-muted mt-2">
                                        Em cursos a partir de R$ {{ number_format(config('cepe.pagamentos.parcelamento_cursos.valor_minimo'), 2, ',', '.') }},
                                        você pode escolher pagar à vista ou parcelar em {{ (int) config('cepe.pagamentos.parcelamento_cursos.parcelas', 3) }} boletos mensais.
                                        A matrícula é ativada após a confirmação da primeira parcela.
                                    </div>
                                @endif
                            </div>
                            <fieldset id="opcoes-parcelamento" class="mb-4 d-none"
                                      data-curso="{{ in_array($atividade->tipo, config('cepe.grupos_catalogo.cursos'), true) ? '1' : '0' }}"
                                      data-minimo="{{ (int) config('cepe.pagamentos.parcelamento_cursos.valor_minimo', 300) }}"
                                      disabled>
                                <legend class="form-label fw-bold font-weight-bold">Opções de parcelamento</legend>
                                <p class="small text-muted">
                                    Escolha pagar à vista ou em boletos mensais sem juros.
                                </p>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="parcelas" id="parcelas_1" value="1" @checked(old('parcelas') === '1')>
                                    <label class="form-check-label" for="parcelas_1"><span data-parcelas-label="1">1x à vista</span></label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="parcelas" id="parcelas_2" value="2" @checked(old('parcelas') === '2')>
                                    <label class="form-check-label" for="parcelas_2"><span data-parcelas-label="2">2x</span></label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="parcelas" id="parcelas_3" value="3" @checked(old('parcelas') === '3')>
                                    <label class="form-check-label" for="parcelas_3"><span data-parcelas-label="3">3x</span></label>
                                </div>
                            </fieldset>
                        @endif

                        <div class="mb-4">
                            <label class="form-label fw-bold font-weight-bold">Autorização de uso de imagem</label>
                            <p class="small text-muted">O CEPEUSP poderá utilizar imagens captadas durante as atividades em seus canais de divulgação.</p>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="image_use_authorization" id="img_sim" value="autoriza" {{ old('image_use_authorization') === 'autoriza' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="img_sim">Autorizo o uso da minha imagem</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="image_use_authorization" id="img_nao" value="nao_autoriza" {{ old('image_use_authorization') === 'nao_autoriza' ? 'checked' : '' }}>
                                <label class="form-check-label" for="img_nao">Não autorizo o uso da minha imagem</label>
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="regulamento" id="regulamento" value="1" {{ old('regulamento') ? 'checked' : '' }} required>
                            <label class="form-check-label" for="regulamento">
                                Li e aceito o regulamento da atividade
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary">Confirmar inscrição</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('javascripts_bottom')
    @parent
    <script>
        $(function () {
            var $formularioInscricao = $('#secao-formulario-inscricao form');
            var $opcoesParcelamento = $formularioInscricao.find('#opcoes-parcelamento');
            var $parcelas = $opcoesParcelamento.find('input[name="parcelas"]');
            var $metodosPagamento = $formularioInscricao.find('#metodos-pagamento');
            var $metodos = $metodosPagamento.find('input[name="metodo_pagamento"]');

            function atualizarOpcoesPagamento() {
                var $perfil = $formularioInscricao.find('input[name="perfil"]:checked');
                if (! $perfil.length) {
                    $perfil = $formularioInscricao.find('input[name="perfil"]').first();
                }

                var valor = Number($perfil.data('valor')) || 0;
                var valorPago = valor > 0;
                var valorCentavos = Math.round(valor * 100);
                var metodo = $metodos.filter(':checked').val();
                var parcelamentoDisponivel = valorPago
                    && metodo === 'boleto'
                    && $opcoesParcelamento.data('curso') === 1
                    && valor >= Number($opcoesParcelamento.data('minimo'));

                $metodos.prop('disabled', ! valorPago).prop('required', valorPago);
                $metodosPagamento.toggleClass('d-none', ! valorPago);
                if (! valorPago) {
                    $metodos.prop('checked', false);
                }

                $opcoesParcelamento.toggleClass('d-none', ! parcelamentoDisponivel);
                $opcoesParcelamento.prop('disabled', ! parcelamentoDisponivel);
                $parcelas.prop('required', parcelamentoDisponivel);
                if (! parcelamentoDisponivel) {
                    $parcelas.prop('checked', false);
                } else {
                    $opcoesParcelamento.find('[data-parcelas-label]').each(function () {
                        var totalParcelas = Number($(this).data('parcelas-label'));
                        var valorBase = Math.floor(valorCentavos / totalParcelas);
                        var resto = valorCentavos % totalParcelas;
                        var grupos = [];
                        var maiores = resto;
                        var menores = totalParcelas - resto;

                        if (maiores) {
                            grupos.push(maiores + 'x de R$ ' + ( (valorBase + 1) / 100).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        }
                        if (menores) {
                            grupos.push(menores + 'x de R$ ' + (valorBase / 100).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        }

                        $(this).text(totalParcelas === 1
                            ? grupos.join(' + ') + ' (à vista)'
                            : grupos.join(' + ') + ' (sem juros)');
                    });
                }
            }

            $formularioInscricao.on('change', 'input[name="perfil"], input[name="metodo_pagamento"]', atualizarOpcoesPagamento);
            atualizarOpcoesPagamento();

            var $caixa = $('#caixa-fila');
            var statusUrl = $caixa.data('status-url');
            var estado = $caixa.data('fila-state'); // fora | aguardando | vez
            if (!statusUrl || estado === 'fora') return;

            var INTERVALO_AGUARDANDO = 3000;  // polling rápido enquanto espera vaga
            var INTERVALO_VEZ = 15000;        // heartbeat para manter a vez enquanto preenche o form

            function verificarStatus() {
                $.ajax({
                    url: statusUrl,
                    type: 'GET',
                    dataType: 'json',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (dados) {
                        if (!dados) return;

                        // Mudança de estado muda o markup da página: recarrega
                        if (!dados.na_fila || dados.is_head !== (estado === 'vez')) {
                            window.location.reload();
                            return;
                        }

                        $('#fila-posicao').text(dados.posicao);
                        $('#fila-head').text(dados.head);
                    },
                    error: function (xhr, status, error) {
                        console.warn('Erro ao verificar status da fila:', error);
                    }
                });
            }

            setInterval(verificarStatus, estado === 'vez' ? INTERVALO_VEZ : INTERVALO_AGUARDANDO);
        });
    </script>
@endsection
