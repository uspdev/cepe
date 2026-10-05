@extends('layout')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Atestados</h1>
        <a href="/atestados/exportar?{{ http_build_query(request()->query()) }}" class="btn btn-outline-secondary">Baixar CSV</a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="/atestados" method="GET" class="form-row">
                <div class="col-md-4 mb-2"><input type="text" name="search" class="form-control" placeholder="Nome ou e-mail" value="{{ request('search') }}"></div>
                <div class="col-md-3 mb-2">
                    <select name="status" class="form-control">
                        <option value="todos">Todos os status</option>
                        @foreach(config('cepe.atestados.status') as $k => $n)
                            <option value="{{ $k }}" @selected(request('status', 'em_analise') == $k)>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <select name="tipo" class="form-control">
                        <option value="">Todos os tipos</option>
                        @foreach(config('cepe.atestados.tipos') as $k => $n)
                            <option value="{{ $k }}" @selected(request('tipo') == $k)>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2"><button type="submit" class="btn btn-primary btn-block">Filtrar</button></div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light"><tr><th>Usuário</th><th>Tipo</th><th>Emissão</th><th>Validade</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($atestados as $a)
                        <tr>
                            <td>{{ $a->user?->name }}<br><small class="text-muted">{{ $a->user?->email }}</small></td>
                            <td>{{ $a->tipo_nome }}</td>
                            <td>{{ $a->emitido_em->format('d/m/Y') }}</td>
                            <td>{{ $a->valido_ate?->format('d/m/Y') ?? '-' }}</td>
                            <td><span class="badge badge-secondary">{{ $a->status_nome }}</span></td>
                            <td class="text-right"><a href="/atestados/{{ $a->id }}" class="btn btn-sm btn-outline-primary">Analisar</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum documento encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $atestados->links() }}</div>
</div>
@endsection
