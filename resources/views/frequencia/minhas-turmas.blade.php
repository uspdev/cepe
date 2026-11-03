@extends('layout')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h1 class="h4 mb-0 text-primary">Minhas turmas</h1>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Atividade</th>
                            <th>Turma</th>
                            <th class="text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($turmas as $turma)
                            <tr>
                                <td>{{ $turma->oferecimento?->atividade?->nome ?? '—' }}</td>
                                <td class="small">{{ $turma->dias_semana_formatados }} {{ $turma->horario_formatado }}</td>
                                <td class="text-end">
                                    <a href="{{ route('frequencia.index', $turma) }}" class="btn btn-sm btn-outline-primary">Frequência</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">Você não está vinculado a nenhuma turma.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
