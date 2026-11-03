@extends('layout')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h1 class="h4 mb-0 text-primary">Chamada de {{ $chamada->data->format('d/m/Y') }}</h1>
            <a href="{{ route('frequencia.index', $turma) }}" class="btn btn-outline-secondary btn-sm">&larr; Voltar</a>
        </div>

        <form method="POST" action="{{ route('frequencia.data', [$turma, $chamada]) }}" class="card-body border-bottom">
            @csrf
            @method('PATCH')
            <div class="form-inline">
                <label for="data-chamada" class="mr-2">Data da chamada</label>
                <input type="date" id="data-chamada" name="data" value="{{ $chamada->data->toDateString() }}" class="form-control mr-2" required>
                <button type="submit" class="btn btn-outline-primary">Corrigir data</button>
            </div>
        </form>

        @if($matriculas->isEmpty())
            <div class="card-body text-center py-4 text-muted">Nenhum aluno matriculado nesta turma.</div>
        @else
            <form method="POST" action="{{ route('frequencia.update', [$turma, $chamada]) }}">
                @csrf
                @method('PATCH')
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Aluno</th>
                                @foreach(config('cepe.status_presenca') as $status => $rotulo)
                                    <th class="text-center">{{ $rotulo }}</th>
                                @endforeach
                                <th>Origem</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($matriculas as $matricula)
                                @php($presenca = $matricula->presencas->first())
                                <tr>
                                    <td>{{ $matricula->user?->name ?? '—' }}</td>
                                    @foreach(config('cepe.status_presenca') as $status => $rotulo)
                                        <td class="text-center">
                                            <input type="radio" name="presencas[{{ $matricula->id }}]" value="{{ $status }}" aria-label="{{ $rotulo }}" {{ $presenca?->status === $status ? 'checked' : '' }}>
                                        </td>
                                    @endforeach
                                    <td class="small text-muted">{{ $presenca?->origem ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white text-right py-3">
                    <button type="submit" class="btn btn-primary">Salvar frequência</button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
