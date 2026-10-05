@extends('layout')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm col-md-8 mx-auto p-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h1 class="h4 mb-0 text-primary">{{ $atestado->tipo_nome }}</h1>
            <a href="/atestados" class="btn btn-outline-secondary btn-sm">&larr; Voltar</a>
        </div>
        <div class="card-body">
            <p><strong>Usuário:</strong> {{ $atestado->user?->name }} ({{ $atestado->user?->email }})</p>
            <p><strong>Emissão:</strong> {{ $atestado->emitido_em->format('d/m/Y') }}</p>
            <p><strong>Validade:</strong> {{ $atestado->valido_ate?->format('d/m/Y') ?? '-' }}</p>
            <p><strong>Status:</strong> {{ $atestado->status_nome }}
                @if($atestado->analisador) <small class="text-muted">por {{ $atestado->analisador->name }} em {{ $atestado->analisado_em?->format('d/m/Y H:i') }}</small>@endif
            </p>
            @if($atestado->observacao)<p><strong>Observação:</strong> {{ $atestado->observacao }}</p>@endif

            @if($atestado->arquivo)
                <a href="/atestados/{{ $atestado->id }}/arquivo" class="btn btn-outline-primary mb-3">Baixar arquivo</a>
            @endif

            @if($atestado->parq)
                <h6 class="mt-3">Respostas do PAR-Q</h6>
                <ul>
                    @foreach(config('cepe.atestados.parq.perguntas') as $n => $texto)
                        <li>{{ $texto }} <strong>{{ strtoupper($atestado->parq->respostas[$n] ?? '-') }}</strong></li>
                    @endforeach
                </ul>
                <p class="text-muted">Termo aceito: {{ $atestado->parq->termo_aceito ? 'sim' : 'não' }} ({{ $atestado->parq->aceite_ip }}, {{ $atestado->parq->aceito_em?->format('d/m/Y H:i') }})</p>
            @endif
        </div>

        <div class="card-footer bg-white">
            <form method="POST" action="/atestados/{{ $atestado->id }}/analise">
                @csrf @method('PATCH')
                @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
                <div class="form-group">
                    <label for="observacao">Observação (obrigatória ao reprovar)</label>
                    <textarea class="form-control" id="observacao" name="observacao" rows="2">{{ old('observacao', $atestado->observacao) }}</textarea>
                </div>
                <button type="submit" name="status" value="aprovado" class="btn btn-success">Aprovar</button>
                <button type="submit" name="status" value="reprovado" class="btn btn-warning text-white">Reprovar</button>
            </form>
            <form action="/atestados/{{ $atestado->id }}" method="POST" class="mt-3">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja apagar?');">Apagar</button>
            </form>
        </div>
    </div>
</div>
@endsection
