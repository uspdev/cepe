@extends('layout')

@section('content')
@php($perfil = $carteira->solicitante?->perfil)
<div class="container py-4">
    <div class="card shadow-sm col-md-8 mx-auto p-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h1 class="h4 mb-0 text-primary">Solicitação de carteira #{{ $carteira->id }}</h1>
            <a href="/carteiras" class="btn btn-outline-secondary btn-sm">&larr; Voltar</a>
        </div>
        <div class="card-body">
            <p>
                <strong>Titular:</strong>
                <a href="/usuarios/{{ $carteira->solicitante_id }}/perfil">{{ $carteira->solicitante?->name }}</a>
                ({{ $carteira->solicitante?->email }})
                @if($perfil?->vinculo_usp)<br><small class="text-muted">{{ $perfil->vinculo_usp }}{{ $perfil->unidade_usp ? ' — '.$perfil->unidade_usp : '' }}</small>@endif
            </p>
            @if($carteira->user)
                <p><strong>Conta vinculada:</strong> <a href="/usuarios/{{ $carteira->user_id }}/perfil">{{ $carteira->user->name }}</a></p>
            @endif
            @include('carteiras.partials.dados', ['carteira' => $carteira])
        </div>

        @if($carteira->proximosStatus() || $carteira->numero)
            <div class="card-footer bg-white">
                <form method="POST" action="/carteiras/{{ $carteira->id }}/analise">
                    @csrf @method('PATCH')
                    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div>@endif
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="numero">Número da carteira</label>
                            <input type="text" class="form-control{{ $errors->has('numero') ? ' is-invalid' : '' }}" id="numero" name="numero" value="{{ old('numero', $carteira->numero) }}">
                            @if($carteira->config('prefixos'))<small class="form-text text-muted">Começa com {{ implode(' ou ', $carteira->config('prefixos')) }}.</small>@endif
                        </div>
                        <div class="form-group col-md-2">
                            <label for="via">Via</label>
                            <input type="number" min="1" class="form-control{{ $errors->has('via') ? ' is-invalid' : '' }}" id="via" name="via" value="{{ old('via', $carteira->via) }}">
                        </div>
                        <div class="form-group col-md-7">
                            <label for="observacao">Observação (obrigatória para pendência ou reprovação)</label>
                            <textarea class="form-control" id="observacao" name="observacao" rows="2">{{ old('observacao', $carteira->observacao) }}</textarea>
                        </div>
                    </div>
                    @foreach($carteira->proximosStatus() as $proximo)
                        <button type="submit" name="status" value="{{ $proximo }}" class="btn {{ $proximo === 'aprovada' ? 'btn-success' : ($proximo === 'pendente' ? 'btn-info' : ($proximo === 'reprovada' ? 'btn-warning text-white' : ($proximo === 'inativa' ? 'btn-outline-danger' : 'btn-outline-primary'))) }}">{{ config('cepe.carteiras.status.'.$proximo) }}</button>
                    @endforeach
                    @if($carteira->numero)
                        <button type="submit" name="status" value="{{ $carteira->status }}" class="btn btn-outline-secondary">Salvar número/via</button>
                    @endif
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
