@extends('layout')

@section('content')

Atividade_id: {{ $oferecimento->atividade_id }} <br>
<a href="/oferecimentos">Voltar</a>
<a href="/oferecimentos/{{ $oferecimento->id }}/edit">Editar</a> <br>

<form action="/oferecimentos/{{ $oferecimento->id }} " method="post">
    @csrf
    @method('delete')
    <button type="submit" onclick="return confirm('Tem certeza?');">Apagar</button> 
</form>
@endsection