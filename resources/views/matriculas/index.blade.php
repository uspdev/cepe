@extends('layout')

@section('content')
<a href="/matriculas/create" class="btn btn-success">Cadastrar novo matricula</a><br><br>
<form class="form">
    <input type="text" name="search" value="{{ request('search') }}">
    <button type="submit">Pesquisar</button>
</form>

<h1>Listagem de Matriculas</h1>
<ul>
    @foreach($matriculas as $matricula)
        <li><a href="/matriculas/{{ $matricula->id}}">{{ $matricula->a }}</a></li>
    @endforeach
</ul>
@endsection