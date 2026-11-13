@extends('layout')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Pagamentos</h1>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('boletos.index') }}" method="GET" class="form-row">
                <div class="col-md-5 mb-2">
                    <input type="text" name="search" class="form-control" placeholder="Nome ou e-mail" value="{{ request('search') }}">
                </div>
                <div class="col-md-3 mb-2">
                    <select name="status" class="form-control">
                        <option value="">Todos os status</option>
                        @foreach(config('cepe.pagamentos.status') as $chave => $nome)
                            <option value="{{ $chave }}" @selected(request('status') === $chave)>{{ $nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <div class="form-check pt-2">
                        <input type="checkbox" name="revisao_credito_sru" value="1" class="form-check-input" id="revisao_credito_sru" @checked(request()->boolean('revisao_credito_sru'))>
                        <label class="form-check-label" for="revisao_credito_sru">Revisão SRU</label>
                    </div>
                </div>
                <div class="col-md-2 mb-2">
                    <button type="submit" class="btn btn-primary btn-block">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Usuário</th>
                        <th>Atividade</th>
                        <th>Método</th>
                        <th>Parcela</th>
                        <th>Valor</th>
                        <th>Vencimento</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cobrancas as $cobranca)
                        @php
                            $matricula = $cobranca->matricula;
                        @endphp
                        <tr>
                            <td>{{ $matricula?->user?->name }}<br><small class="text-muted">{{ $matricula?->user?->email }}</small></td>
                            <td>{{ $matricula?->turma?->oferecimento?->atividade?->nome ?? '—' }}</td>
                            <td>{{ $cobranca->metodo_nome }}</td>
                            <td>{{ $cobranca->parcela }}/{{ $cobranca->total_parcelas }}</td>
                            <td>
                                R$ {{ number_format($cobranca->valorReais(), 2, ',', '.') }}
                                @if($cobranca->valor_pago_centavos !== null)
                                    <br><small class="text-muted">Pago: R$ {{ number_format($cobranca->valor_pago_centavos / 100, 2, ',', '.') }}</small>
                                @endif
                            </td>
                            <td>{{ $cobranca->vencimento->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge badge-secondary">{{ $cobranca->status_nome }}</span>
                                @if($matricula?->revisao_credito_sru)
                                    <div><span class="badge badge-warning mt-1">Revisão de crédito SRU</span></div>
                                @endif
                            </td>
                            <td class="text-right">
                                @if($cobranca->codigo_boleto)
                                    <a href="{{ route('minhas-inscricoes.boletos.pdf', [$matricula, $cobranca]) }}" class="btn btn-sm btn-outline-primary">Ver boleto</a>
                                @elseif($cobranca->id_pix)
                                    <span class="small text-muted">{{ $cobranca->id_pix }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-4 text-muted">Nenhuma cobrança encontrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $cobrancas->links() }}</div>
</div>
@endsection
