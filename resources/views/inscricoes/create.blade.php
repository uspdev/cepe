@extends('layout')

@section('content')
@php($oferecimento = $turma->oferecimento)
@php($atividade = $oferecimento->atividade)
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
                    @if($turma->professor)<li><strong>Professor:</strong> {{ $turma->professor }}</li>@endif
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
                                                    @if($dados['valor'] !== null)
                                                        — R$ {{ number_format($dados['valor'], 2, ',', '.') }}
                                                    @endif
                                                </label>
                                            </div>
                                        @endforeach
                                    @else
                                        @php($dados = reset($perfisElegiveis))
                                        <input type="hidden" name="perfil" value="{{ key($perfisElegiveis) }}">
                                        <p class="mb-0">
                                            {{ $dados['label'] }}
                                            @if($dados['valor'] !== null)
                                                — R$ {{ number_format($dados['valor'], 2, ',', '.') }}
                                            @endif
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
                                <input type="hidden" name="perfil" value="{{ $minhaFila->perfil }}">
                                <p class="mb-0">{{ config('cepe.perfil.' . $minhaFila->perfil, $minhaFila->perfil) }}</p>
                            @elseif(count($perfisLivres) === 1)
                                {{-- Único perfil disponível: exibe fixo, sem escolha --}}
                                @php($dados = reset($perfisLivres))
                                <input type="hidden" name="perfil" value="{{ key($perfisLivres) }}">
                                <p class="mb-0">
                                    {{ $dados['label'] }}
                                    @if($dados['valor'] !== null)
                                        — R$ {{ number_format($dados['valor'], 2, ',', '.') }}
                                    @endif
                                </p>
                            @else
                                @foreach($perfisLivres as $perfil => $dados)
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="perfil" id="perfil_{{ $perfil }}" value="{{ $perfil }}" {{ old('perfil') === $perfil ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="perfil_{{ $perfil }}">
                                            {{ $dados['label'] }}
                                            @if($dados['valor'] !== null)
                                                — R$ {{ number_format($dados['valor'], 2, ',', '.') }}
                                            @endif
                                            <span class="text-muted small">({{ $dados['vagas'] }} {{ $dados['vagas'] > 1 ? 'vagas' : 'vaga' }})</span>
                                        </label>
                                    </div>
                                @endforeach
                            @endif
                        </div>

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
