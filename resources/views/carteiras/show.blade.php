@extends('layout')

@section('content')
@php($perfil = $solicitacao->solicitante?->perfil)
<div class="container py-4">
    <div class="card shadow-sm col-md-8 mx-auto p-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h1 class="h4 mb-0 text-primary">Solicitação de carteira #{{ $solicitacao->id }}</h1>
            <a href="/carteiras" class="btn btn-outline-secondary btn-sm">&larr; Voltar</a>
        </div>
        <div class="card-body">
            <p>
                <strong>Titular:</strong>
                <a href="/usuarios/{{ $solicitacao->solicitante_id }}/perfil">{{ $solicitacao->solicitante?->name }}</a>
                ({{ $solicitacao->solicitante?->email }})
                @if($perfil?->vinculo_usp)<br><small class="text-muted">{{ $perfil->vinculo_usp }}{{ $perfil->unidade_usp ? ' — '.$perfil->unidade_usp : '' }}</small>@endif
            </p>
            @if($solicitacao->carteira?->user)
                <p><strong>Conta vinculada:</strong> <a href="/usuarios/{{ $solicitacao->carteira->user_id }}/perfil">{{ $solicitacao->carteira->user->name }}</a></p>
            @endif
            @include('carteiras.partials.dados', ['solicitacao' => $solicitacao])
        </div>

        @if($solicitacao->proximosStatus())
            <div class="card-footer bg-white">
                <form method="POST" action="/carteiras/{{ $solicitacao->id }}/analise">
                    @csrf @method('PATCH')
                    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div>@endif
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="numero">Número da carteira</label>
                            <input type="text" class="form-control{{ $errors->has('numero') ? ' is-invalid' : '' }}" id="numero" name="numero" value="{{ old('numero', $solicitacao->carteira?->numero) }}">
                            @if($solicitacao->config('prefixos'))<small class="form-text text-muted">Começa com {{ implode(' ou ', $solicitacao->config('prefixos')) }}.</small>@endif
                        </div>
                        <div class="form-group col-md-2">
                            <label for="via">Via</label>
                            <input type="number" min="1" class="form-control{{ $errors->has('via') ? ' is-invalid' : '' }}" id="via" name="via" value="{{ old('via', $solicitacao->carteira?->via) }}">
                        </div>
                        <div class="form-group col-md-7">
                            <label for="observacao">Observação (obrigatória para pendência ou reprovação)</label>
                            <textarea class="form-control" id="observacao" name="observacao" rows="2">{{ old('observacao', $solicitacao->observacao) }}</textarea>
                        </div>
                    </div>
                    @foreach($solicitacao->proximosStatus() as $proximo)
                        <button type="submit" name="status" value="{{ $proximo }}" class="btn {{ $proximo === 'aprovada' ? 'btn-success' : ($proximo === 'pendente' ? 'btn-info' : ($proximo === 'reprovada' ? 'btn-warning text-white' : ($proximo === 'inativa' ? 'btn-outline-danger' : 'btn-outline-primary'))) }}">{{ config('cepe.carteiras.status.'.$proximo) }}</button>
                    @endforeach
                </form>
            </div>
        @endif

        @if($solicitacao->carteira?->proximosStatus())
            <div class="card-footer bg-white">
                <h2 class="h6">Ciclo da carteira emitida</h2>
                <p>Status atual: {{ $solicitacao->carteira->status_nome }}</p>
                <form method="POST" action="/carteiras/emitidas/{{ $solicitacao->carteira->id }}/status">
                    @csrf @method('PATCH')
                    @foreach($solicitacao->carteira->proximosStatus() as $proximo)
                        <button type="submit" name="status" value="{{ $proximo }}" class="btn btn-outline-primary">{{ config('cepe.carteiras.status.'.$proximo, $proximo) }}</button>
                    @endforeach
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
