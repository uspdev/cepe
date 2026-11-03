@extends('layout')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h1 class="h4 mb-0 text-primary">Frequência</h1>
            <a href="{{ route('minhas-turmas.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Voltar</a>
        </div>
        <div class="card-body">
            <p class="mb-1"><strong>Atividade:</strong> {{ $turma->oferecimento?->atividade?->nome ?? '—' }}</p>
            <p class="mb-0"><strong>Horário:</strong> {{ $turma->dias_semana_formatados }} {{ $turma->horario_formatado }}</p>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h2 class="h5 mb-0">Chamada de hoje</h2>
        </div>
        <div class="card-body">
            <a href="{{ route('frequencia.hoje', $turma) }}" class="btn btn-primary">Registrar presença de hoje</a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h2 class="h5 mb-0">Chamadas</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Alunos registrados</th>
                            <th class="text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($chamadas as $chamada)
                            <tr>
                                <td>{{ $chamada->data->format('d/m/Y') }}</td>
                                <td>{{ $chamada->presencas_count }}</td>
                                <td class="text-end">
                                    <a href="{{ route('frequencia.show', [$turma, $chamada]) }}" class="btn btn-sm btn-outline-primary">Abrir</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">Nenhuma chamada aberta.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
