@extends('layout')

@section('content')
<div class="container py-4">
    <!-- Cabeçalho com Título e Botão de Ação -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-dark">Listagem de Atividades</h1>
        <a href="/atividades/create" class="btn btn-success">
            + Cadastrar nova atividade
        </a>
    </div>

    <!-- Barra de Pesquisa -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="/atividades" method="GET" class="row g-2">
                <div class="col">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Buscar por nome ou descrição..." value="{{ request('search') }}">
                        <button type="submit" class="btn btn-primary">Pesquisar</button>
                        @if(request('search'))
                            <a href="/atividades" class="btn btn-outline-secondary">Limpar filtro</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Conteúdo -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>Descrição</th>
                            <th class="text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($atividades as $atividade)
                            <tr>
                                <td class="fw-bold">
                                    <a href="/atividades/{{ $atividade->id }}" class="text-decoration-none text-primary">
                                        {{ $atividade->nome }}
                                    </a>
                                </td>
                                <td class="text-muted">
                                    {{ $atividade->descricao }}
                                </td>
                                <td class="text-end">
                                    <a href="/atividades/{{ $atividade->id }}" class="btn btn-sm btn-outline-primary">
                                        Ver detalhes
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">
                                    Nenhuma atividade encontrada.
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