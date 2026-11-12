@extends('layout')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm col-md-8 mx-auto p-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h1 class="h4 mb-0 text-primary">Solicitação de carteira de {{ $solicitacao->nome }}</h1>
            <a href="/minhas-carteiras" class="btn btn-outline-secondary btn-sm">&larr; Voltar</a>
        </div>
        <div class="card-body">
            @include('carteiras.partials.dados', ['solicitacao' => $solicitacao])
        </div>

        @can('reenviar', $solicitacao)
            <div class="card-footer bg-white">
                <h6>Reenviar documentos</h6>
                <p class="text-muted small">Envie apenas os documentos indicados na observação da SRU; os demais permanecem.</p>
                <form method="POST" action="/minhas-carteiras/{{ $solicitacao->id }}/documentos" enctype="multipart/form-data">
                    @csrf
                    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
                    @foreach($tipos as $tipo)
                        <div class="form-group">
                            <label for="doc_{{ $tipo }}">{{ config('cepe.carteiras.documentos.'.$tipo) }}</label>
                            <input type="file" class="form-control-file" id="doc_{{ $tipo }}" name="documentos[{{ $tipo }}]" accept="{{ $tipo === 'foto' ? '.jpg,.jpeg' : '.pdf,.jpg,.jpeg,.png' }}">
                        </div>
                    @endforeach
                    <button type="submit" class="btn btn-primary">Reenviar</button>
                </form>
            </div>
        @endcan
    </div>
</div>
@endsection
