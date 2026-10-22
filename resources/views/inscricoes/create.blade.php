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
                <label class="form-label text-muted fw-bold">Turma</label>
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
                <form method="POST" action="{{ route('inscricoes.store', $turma) }}">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label fw-bold">Perfil de inscrição</label>
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
                        <label class="form-label fw-bold">Autorização de uso de imagem</label>
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
            @endif
        </div>
    </div>
</div>
@endsection
