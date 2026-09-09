@extends('layout')

@section('content')

<form action="/oferecimentos/{{ $atividade->id }}" method="POST" class="p-4 bg-white rounded border shadow-sm">
    @csrf
    <input type="hidden" name="atividade_id" value="{{ $atividade->id }}">

    <h4 class="mb-4 text-primary">{{ $atividade->nome }}</h4>

    @include('oferecimentos.partials.form')

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Enviar</button>
        <a href="/atividades/{{ $atividade->id }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

@include('oferecimentos.partials.scripts')
@endsection
