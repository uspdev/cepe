@extends('layout')

@section('content')

<form method="POST" action="/turmas/{{ $turma->id }}">
    @csrf
    @method('PATCH')
    Oferecimento_id: <input type="text" name="oferecimento_id" value="{{ old('oferecimento_id', $turma->oferecimento_id) }}">
    <button type="submit">Enviar</button>
</form>
@endsection