@extends('layout')

@section('content')

<form method="POST" action="/matriculas/{{ $matricula->id }}">
    @csrf
    @method('PATCH')
    A: <input type="text" name="a" value="{{ old('a', $matricula->a) }}">
    <button type="submit">Enviar</button>
</form>
@endsection