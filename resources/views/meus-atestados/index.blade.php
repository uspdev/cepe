@extends('layout')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Meus Atestados</h1>
        <div>
            @foreach(config('cepe.atestados.tipos') as $key => $nome)
                <a href="/meus-atestados/create?tipo={{ $key }}" class="btn btn-success btn-sm mb-1">+ Enviar {{ $nome }}</a>
            @endforeach
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr><th>Tipo</th><th>Emissão</th><th>Validade</th><th>Status</th><th>Observação</th></tr>
                </thead>
                <tbody>
                    @forelse($atestados as $atestado)
                        <tr>
                            <td>{{ $atestado->tipo_nome }}</td>
                            <td>{{ $atestado->emitido_em->format('d/m/Y') }}</td>
                            <td>{{ $atestado->valido_ate?->format('d/m/Y') ?? '-' }}</td>
                            <td><span class="badge badge-secondary">{{ $atestado->status_nome }}</span></td>
                            <td>{{ $atestado->observacao }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">Nenhum documento enviado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
