@extends('layout')

@section('content')
<div class="container py-4">
    <h1 class="h3 mb-4">Solicitações de Carteira CEPEUSP</h1>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="/carteiras" method="GET" class="form-row">
                <div class="col-md-4 mb-2"><input type="text" name="search" class="form-control" placeholder="Beneficiário, titular, e-mail ou número" value="{{ request('search') }}"></div>
                <div class="col-md-3 mb-2">
                    <select name="status" class="form-control">
                        <option value="todos">Todos os status</option>
                        @foreach(array_keys(\App\Models\SolicitacaoCarteira::TRANSICOES) as $k)
                            @php($n = config('cepe.carteiras.status.'.$k, $k))
                            <option value="{{ $k }}" @selected((request()->has('status') ? request('status') : 'em_analise') == $k)>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <select name="categoria" class="form-control">
                        <option value="">Todas as categorias</option>
                        @foreach(config('cepe.carteiras.categorias') as $k => $c)
                            <option value="{{ $k }}" @selected(request('categoria') == $k)>{{ $c['rotulo'] }}</option>
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
                <thead class="thead-light"><tr><th>Beneficiário</th><th>Titular</th><th>Categoria</th><th>Número</th><th>Validade</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($solicitacoes as $c)
                        <tr>
                            <td>{{ $c->nome }}@if($c->parentesco_nome)<br><small class="text-muted">{{ $c->parentesco_nome }}</small>@endif</td>
                            <td>{{ $c->solicitante?->name }}<br><small class="text-muted">{{ $c->solicitante?->email }}</small></td>
                            <td>{{ $c->categoria_nome }}</td>
                            <td>{{ $c->carteira?->numero ?? '-' }}@if($c->carteira?->via)<br><small class="text-muted">{{ $c->carteira->via }}ª via</small>@endif</td>
                            <td>{{ $c->carteira?->validade_fim?->format('d/m/Y') ?? '-' }}</td>
                            <td><span class="badge badge-secondary">{{ $c->status_nome }}</span></td>
                            <td class="text-right"><a href="/carteiras/{{ $c->id }}" class="btn btn-sm btn-outline-primary">Analisar</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">Nenhuma solicitação encontrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $solicitacoes->links() }}</div>
</div>
@endsection
