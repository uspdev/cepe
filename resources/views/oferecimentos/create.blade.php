@extends('layout')

@section('content')

<form action="/oferecimentos/{{ $atividade->id }}" method="POST" class="p-4 bg-white rounded border">
    @csrf
    <input type="hidden" name="atividade_id" value="{{ $atividade->id }}">

    <h5 class="mb-3">{{ $atividade->nome }}</h5>

    @include('oferecimentos.partials.form')

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Enviar</button>
        <a href="/atividades/{{ $atividade->id }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

@include('oferecimentos.partials.scripts')
@endsection
