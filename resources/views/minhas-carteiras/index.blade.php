@extends('layout')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Minhas carteiras CEPEUSP</h1>
        @if($categorias)
            <a href="/minhas-carteiras/create" class="btn btn-success btn-sm">+ Solicitar carteira</a>
        @endif
    </div>

    <p class="text-muted small">
        O titular solicita a carteira para si ou para seus dependentes. A carteira vale por um ano e fica disponível para retirada na recepção do CEPEUSP
        de 10 a 20 dias úteis após a confirmação do pagamento. Dúvidas: <a href="mailto:carteiras.cepe@usp.br">carteiras.cepe@usp.br</a>.
    </p>

    @if($carteirasRenovaveis->isNotEmpty())
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6">Renovar carteira</h2>
                @foreach($carteirasRenovaveis as $carteira)
                    <a href="/minhas-carteiras/create?renovar={{ $carteira->id }}" class="btn btn-sm btn-outline-primary mr-2 mb-2">
                        {{ $carteira->numero }} — {{ $carteira->categoria_nome }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr><th>Beneficiário</th><th>Categoria</th><th>Número</th><th>Validade</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($solicitacoes as $solicitacao)
                        <tr>
                            <td>{{ $solicitacao->nome }}@if($solicitacao->parentesco_nome)<br><small class="text-muted">{{ $solicitacao->parentesco_nome }}</small>@endif</td>
                            <td>{{ $solicitacao->categoria_nome }}</td>
                            <td>{{ $solicitacao->carteira?->numero ?? '-' }}@if($solicitacao->carteira?->via)<br><small class="text-muted">{{ $solicitacao->carteira->via }}ª via</small>@endif</td>
                            <td>{{ $solicitacao->carteira?->validade_fim?->format('d/m/Y') ?? '-' }}</td>
                            <td><span class="badge badge-secondary">{{ $solicitacao->status_nome }}</span></td>
                            <td class="text-right text-nowrap">
                                <a href="/minhas-carteiras/{{ $solicitacao->id }}" class="btn btn-sm btn-outline-primary">Detalhes</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">Nenhuma solicitação de carteira.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
