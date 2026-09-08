@extends('layout')

@section('content')
<div class="container py-4">
    <!-- Card de Detalhes da Atividade -->
    <div class="card shadow-sm col-md-8 mx-auto mb-4">
        <!-- Cabeçalho do Card com botão de Voltar -->
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h1 class="h4 mb-0 text-primary">Detalhes da Atividade</h1>
            <a href="/atividades" class="btn btn-outline-secondary btn-sm">
                &larr; Voltar
            </a>
        </div>

        <!-- Corpo com as informações -->
        <div class="card-body">
            <div class="mb-4">
                <label class="form-label text-muted fw-bold">Nome</label>
                <p class="fs-5 fw-semibold mb-0">{{ $atividade->nome }}</p>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted fw-bold">Descrição</label>
                <div class="p-3 bg-light rounded border text-secondary">
                    {{ $atividade->descricao }}
                </div>
            </div>
        </div>

        <!-- Rodapé com os Botões de Ação -->
        <div class="card-footer bg-white d-flex justify-content-end gap-2 py-3">
            <a href="/atividades/{{ $atividade->id }}/edit" class="btn btn-warning text-white">
                Editar
            </a>

            <form action="/atividades/{{ $atividade->id }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja apagar esta atividade?');">
                    Apagar
                </button>
            </form>
        </div>
    </div>

    <!-- Seção de Oferecimentos Relacionados -->
    <div class="card shadow-sm col-md-8 mx-auto">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h2 class="h5 mb-0 text-dark">Oferecimentos</h2>
            <!-- Botão para cadastrar um novo oferecimento desta atividade -->
            <a href="/oferecimentos/{{ $atividade->id }}/create" class="btn btn-success btn-sm">
                + Novo Oferecimento
            </a>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Oferecimento</th>
                            <th class="text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($atividade->oferecimentos as $oferecimento)
                            <tr>
                                <td class="fw-bold">{{ $oferecimento->id }}</td>
                                <td>
                                    {{-- Adapte o campo abaixo conforme os atributos da sua model Oferecimento (ex: $oferecimento->data, $oferecimento->turma, etc) --}}
                                    {{ $oferecimento->nome ?? $oferecimento->descricao ?? 'Oferecimento #' . $oferecimento->id }}
                                </td>
                                <td class="text-end">
                                    <a href="/oferecimentos/{{ $atividade->id }}/{{ $oferecimento->id }}" class="btn btn-sm btn-outline-primary">
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">
                                    Nenhum oferecimento cadastrado para esta atividade.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection