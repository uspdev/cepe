@extends('layout')

@section('content')
<div class="container py-4">
    <h1 class="h3 text-primary mb-4">{{ $grupo === 'cursos' ? 'Cursos' : 'Eventos' }}</h1>

    <div class="row g-4">
        @forelse($atividades as [$atividade, $oferecimento])
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <span class="badge bg-primary align-self-start mb-2">{{ config('cepe.tipos_inscricao.' . $atividade->tipo, $atividade->tipo) }}</span>
                        <h2 class="h5">{{ $atividade->nome }}</h2>
                        <p class="text-muted small flex-grow-1">{{ Str::limit($atividade->descricao, 140) }}</p>
                        <p class="small text-secondary mb-2">
                            Período {{ $oferecimento->periodo }}
                            @if($oferecimento->periodosAbertos()->isNotEmpty())
                                <span class="badge bg-success">Inscrições abertas</span>
                            @endif
                        </p>
                        <a href="{{ route('catalogo.show', $atividade) }}" class="btn btn-outline-primary btn-sm mt-auto">Ver turmas</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">Nenhuma atividade disponível no momento.</div>
            </div>
        @endforelse
    </div>
</div>
@endsection
