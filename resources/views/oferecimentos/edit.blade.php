@extends('layout')

@section('content')

<form method="POST" action="/oferecimentos/{{ $oferecimento->id }}">
    @csrf
    @method('PATCH')
    Atividade_id: <input type="text" name="atividade_id" value="{{ old('atividade_id', $oferecimento->atividade_id) }}">
    <button type="submit">Enviar</button>
</form>
@endsection