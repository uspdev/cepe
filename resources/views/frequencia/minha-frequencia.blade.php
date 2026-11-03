@extends('layout')

@section('content')
<div class="container py-4">
    <h1 class="h4 text-primary mb-4">Minha frequência</h1>

    @forelse($matriculas as $matricula)
        @php($turma = $matricula->turma)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <div>
                    <h2 class="h5 mb-0">{{ $turma?->oferecimento?->atividade?->nome ?? '—' }}</h2>
                    @if($turma)
                        <small class="text-muted">{{ $turma->dias_semana_formatados }} {{ $turma->horario_formatado }}</small>
                    @endif
                </div>
                @php($hoje = now(config('cepe.fuso_frequencia'))->toDateString())
                @can('checkin', $matricula)
                    @if($frequencia->janelaAberta($turma) && ! $matricula->presencas->contains(fn ($presenca) => $presenca->chamada->data->toDateString() === $hoje))
                        <form method="POST" action="{{ route('minhas-inscricoes.presenca', $matricula) }}">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">Registrar presença de hoje</button>
                        </form>
                    @endif
                @endcan
            </div>
            <div class="card-body p-0">
                @if($matricula->presencas->isEmpty())
                    <div class="p-3 text-muted">Nenhuma chamada registrada.</div>
                @else
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Data</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($matricula->presencas->sortByDesc(fn ($presenca) => $presenca->chamada->data) as $presenca)
                                <tr>
                                    <td>{{ $presenca->chamada->data->format('d/m/Y') }}</td>
                                    <td>{{ $presenca->status_nome }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    @empty
        <div class="card shadow-sm">
            <div class="card-body text-center py-4 text-muted">Você não possui inscrições ativas em turmas.</div>
        </div>
    @endforelse
</div>
@endsection
