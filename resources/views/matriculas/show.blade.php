@extends('layout')

@section('content')

A: {{ $matricula->a }} <br>
<a href="/matriculas">Voltar</a>
<a href="/matriculas/{{ $matricula->id }}/edit">Editar</a> <br>

<form action="/matriculas/{{ $matricula->id }} " method="post">
    @csrf
    @method('delete')
    <button type="submit" onclick="return confirm('Tem certeza?');">Apagar</button> 
</form>
@endsection