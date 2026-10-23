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
                {{-- Caixinha da fila de inscrição --}}
                <div id="caixa-fila" class="card mb-4 border-info shadow-sm"
                     data-status-url="{{ route('filas.status', $turma) }}"
                     data-na-fila="{{ $minhaFila ? '1' : '0' }}"
                     data-is-head="{{ $isHead ? '1' : '0' }}">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                        <span class="font-weight-bold fw-bold text-secondary">
                            <i class="fas fa-users mr-1"></i> Fila de Inscrição
                        </span>
                        <span id="fila-badge" class="badge {{ $isHead ? 'badge-success' : ($minhaFila ? 'badge-primary' : 'badge-secondary') }} px-2 py-1">
                            @if(!$minhaFila)
                                Não iniciado
                            @elseif($isHead)
                                Sua vez
                            @else
                                Aguardando
                            @endif
                        </span>
                    </div>
                    <div class="card-body text-center p-4">
                        {{-- Estado 1: Usuário ainda não entrou na fila --}}
                        <div id="fila-estado-fora" class="{{ $minhaFila ? 'd-none' : '' }}">
                            <p class="text-muted mb-3">
                                Para se inscrever nesta turma, entre na fila virtual.
                            </p>
                            <form method="POST" action="{{ route('filas.entrar', $turma) }}" id="form-entrar-fila">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-lg px-4 shadow-sm" id="btn-entrar-fila">
                                    Entrar na fila
                                </button>
                            </form>
                        </div>

                        {{-- Estado 2: Usuário está na fila aguardando --}}
                        <div id="fila-estado-aguardando" class="{{ ($minhaFila && !$isHead) ? '' : 'd-none' }}">
                            <div class="alert alert-info border-info mb-3 p-3">
                                <div class="mb-2">
                                    <div class="spinner-border spinner-border-sm text-info mr-1" role="status">
                                        <span class="sr-only">Aguardando...</span>
                                    </div>
                                    <strong>Fila de espera em andamento</strong>
                                </div>
                                <div class="h5 mb-0" id="texto-fila">
                                    você está na posição <span id="fila-posicao" class="badge badge-primary px-2 py-1">{{ $posicao }}</span> e o usuário de número <span id="fila-head" class="badge badge-secondary px-2 py-1">{{ $head }}</span> esta fazendo inscrição. Aguarde.
                                </div>
                            </div>
                            <small class="text-muted">
                                Aguarde nesta página. A caixinha será atualizada automaticamente quando o atendimento avançar.
                            </small>
                        </div>

                        {{-- Estado 3: É a vez do usuário --}}
                        <div id="fila-estado-vez" class="{{ ($minhaFila && $isHead) ? '' : 'd-none' }}">
                            <div class="alert alert-success border-success mb-0 p-3">
                                <h5 class="alert-heading font-weight-bold fw-bold mb-1">
                                    🎉 É a sua vez de fazer a inscrição!
                                </h5>
                                <p class="mb-0">
                                    Você é o usuário de número <strong><span id="fila-posicao-vez">{{ $posicao }}</span></strong> na fila. Por favor, preencha o formulário abaixo para confirmar sua inscrição.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Formulário de confirmação de inscrição (habilitado quando for a vez do usuário) --}}
                <div id="secao-formulario-inscricao" class="{{ ($minhaFila && $isHead) ? '' : 'd-none' }}">
                    <form method="POST" action="{{ route('inscricoes.store', $turma) }}">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label fw-bold font-weight-bold">Perfil de inscrição</label>
                            @foreach($perfis as $perfil => $dados)
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
            if (!$caixa.length) return;

            var statusUrl = $caixa.data('status-url');
            var timerId = null;

            function atualizarVisualizacao(dados) {
                if (!dados) return;

                if (dados.na_fila) {
                    $('#fila-posicao').text(dados.posicao);
                    $('#fila-head').text(dados.head);
                    $('#fila-posicao-vez').text(dados.posicao);

                    if (dados.is_head) {
                        $('#fila-estado-fora').addClass('d-none');
                        $('#fila-estado-aguardando').addClass('d-none');
                        $('#fila-estado-vez').removeClass('d-none');
                        $('#secao-formulario-inscricao').removeClass('d-none');

                        $('#fila-badge').removeClass('badge-secondary badge-primary')
                                       .addClass('badge-success')
                                       .text('Sua vez');

                        if (timerId) {
                            clearInterval(timerId);
                            timerId = null;
                        }
                    } else {
                        $('#fila-estado-fora').addClass('d-none');
                        $('#fila-estado-vez').addClass('d-none');
                        $('#fila-estado-aguardando').removeClass('d-none');
                        $('#secao-formulario-inscricao').addClass('d-none');

                        $('#fila-badge').removeClass('badge-secondary badge-success')
                                       .addClass('badge-primary')
                                       .text('Aguardando');
                    }
                } else {
                    $('#fila-estado-fora').removeClass('d-none');
                    $('#fila-estado-aguardando').addClass('d-none');
                    $('#fila-estado-vez').addClass('d-none');
                    $('#secao-formulario-inscricao').addClass('d-none');

                    $('#fila-badge').removeClass('badge-primary badge-success')
                                   .addClass('badge-secondary')
                                   .text('Não iniciado');
                }
            }

            function verificarStatus() {
                $.ajax({
                    url: statusUrl,
                    type: 'GET',
                    dataType: 'json',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (dados) {
                        atualizarVisualizacao(dados);
                    },
                    error: function (xhr, status, error) {
                        console.warn('Erro ao verificar status da fila:', error);
                    }
                });
            }

            // Inicia verificação periódica se estiver na fila e ainda não for o head
            var naFilaInicial = $caixa.data('na-fila') == '1';
            var isHeadInicial = $caixa.data('is-head') == '1';

            if (naFilaInicial && !isHeadInicial) {
                timerId = setInterval(verificarStatus, 3000);
            }

            // Submissão do botão "Entrar na fila"
            $('#form-entrar-fila').on('submit', function (e) {
                e.preventDefault();
                var $btn = $('#btn-entrar-fila');
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1" role="status"></span> Entrando na fila...');

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function (dados) {
                        if (dados && dados.success) {
                            atualizarVisualizacao({
                                na_fila: true,
                                posicao: dados.posicao,
                                head: dados.head,
                                is_head: dados.is_head
                            });

                            if (!dados.is_head && !timerId) {
                                timerId = setInterval(verificarStatus, 3000);
                            }
                        } else {
                            window.location.reload();
                        }
                    },
                    error: function () {
                        // Caso a resposta não seja JSON ou ocorra erro, submete normalmente
                        $('#form-entrar-fila').off('submit').submit();
                    }
                });
            });
        });
    </script>
@endsection
