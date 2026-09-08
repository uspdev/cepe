@extends('layout')

@section('content')

Oferecimento_id: {{ $turma->oferecimento_id }} <br>
<a href="/turmas">Voltar</a>
<a href="/turmas/{{ $turma->id }}/edit">Editar</a> <br>

<form action="/turmas/{{ $turma->id }} " method="post">
    @csrf
    @method('delete')
    <button type="submit" onclick="return confirm('Tem certeza?');">Apagar</button> 
</form>
@endsection