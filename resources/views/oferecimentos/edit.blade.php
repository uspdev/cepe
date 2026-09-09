@extends('layout')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm col-md-10 mx-auto p-0">
        <div class="card-header bg-white py-3">
            <h1 class="h4 mb-0 text-primary">Editar Oferecimento #{{ $oferecimento->id }} &mdash; {{ $atividade->nome }}</h1>
        </div>

        <form method="POST" action="/oferecimentos/{{ $atividade->id }}/{{ $oferecimento->id }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="atividade_id" value="{{ $atividade->id }}">

            <div class="card-body">
                @include('oferecimentos.partials.form', ['oferecimento' => $oferecimento])
            </div>

            <div class="card-footer bg-white text-right py-3">
                <a href="/oferecimentos/{{ $atividade->id }}/{{ $oferecimento->id }}" class="btn btn-outline-secondary mr-2">Cancelar</a>
                <button type="submit" class="btn btn-primary">Enviar</button>
            </div>
        </form>
    </div>
</div>

@include('oferecimentos.partials.scripts')
@endsection
