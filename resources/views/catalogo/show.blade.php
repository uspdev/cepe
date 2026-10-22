@extends('layout')

@section('content')
@php($inscricoes = app(App\Services\InscricaoService::class))
<div class="container py-4">
    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm mb-3">&larr; Voltar</a>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h1 class="h3 text-primary">{{ $atividade->nome }}</h1>
            <p class="text-muted">{{ $atividade->descricao }}</p>

            @if($oferecimento)
                <h2 class="h5 mt-4 mb-3">Períodos</h2>
                <div class="row g-3">
                    @foreach($oferecimento->periodos as $periodo)
                        @php($aberto = $periodo->perfil !== 'curso' && $periodo->inicio <= now() && $periodo->fim >= now())
                        <div class="col-md-4 col-lg-3">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-semibold small">{{ config('cepe.periodos.' . $periodo->perfil, $periodo->perfil) }}</div>
                                <div class="small text-muted">{{ $periodo->inicio->format('d/m/Y H:i') }} a {{ $periodo->fim->format('d/m/Y H:i') }}</div>
                                @if($periodo->perfil !== 'curso')
                                    <span class="badge {{ $aberto ? 'bg-success' : 'bg-secondary' }} mt-1">{{ $aberto ? 'Aberto' : 'Fechado' }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <h2 class="h5 mb-3">Turmas</h2>
    <div class="row g-4">
        @forelse($oferecimento?->turmas ?? [] as $i => $turma)
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h3 class="h6 text-primary">Turma {{ $i + 1 }}</h3>
                        <ul class="list-unstyled small mb-3 flex-grow-1">
                            @if($turma->professor)<li><strong>Professor:</strong> {{ $turma->professor }}</li>@endif
                            @if($turma->dias_semana_formatados)<li><strong>Dias:</strong> {{ $turma->dias_semana_formatados }}</li>@endif
                            <li><strong>Horário:</strong> {{ $turma->horario_formatado }}</li>
                            @if($turma->idade_minima || $turma->idade_maxima)
                                <li><strong>Idade:</strong> {{ $turma->idade_minima ?? '—' }} a {{ $turma->idade_maxima ?? '—' }} anos</li>
                            @endif
                            @if($turma->nivel)<li><strong>Nível:</strong> {{ $turma->nivel }}</li>@endif
                            @if($turma->local)<li><strong>Local:</strong> {{ $turma->local }}</li>@endif
                            @if($turma->observacoes)<li><strong>Observações:</strong> {{ $turma->observacoes }}</li>@endif
                            @if($turma->info_contato)<li><strong>Contato:</strong> {{ $turma->info_contato }}</li>@endif
                        </ul>

                        @php($disponiveis = collect($inscricoes->perfisPermitidos($turma))->sum(fn ($p) => max(0, $inscricoes->vagasDisponiveis($turma, $p))))
                        @if($disponiveis > 0)
                            <a href="{{ route('inscricoes.create', $turma) }}" class="btn btn-primary btn-sm w-100">
                                {{ $disponiveis }} {{ $disponiveis > 1 ? 'vagas disponíveis' : 'vaga disponível' }}
                            </a>
                        @elseif($inscricoes->periodosAbertos($turma->oferecimento) === [])
                            <button class="btn btn-secondary btn-sm w-100" disabled>Período fechado</button>
                        @else
                            <button class="btn btn-secondary btn-sm w-100" disabled>Vagas esgotadas</button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">Nenhuma turma cadastrada para esta atividade.</div>
            </div>
        @endforelse
    </div>
</div>
@endsection
