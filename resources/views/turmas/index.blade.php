@extends('layout')

@section('content')
<a href="/turmas/create" class="btn btn-success">Cadastrar novo turma</a><br><br>
<form class="form">
    <input type="text" name="search" value="{{ request('search') }}">
    <button type="submit">Pesquisar</button>
</form>

<h1>Listagem de Turmas</h1>
<ul>
    @foreach($turmas as $turma)
        <li><a href="/turmas/{{ $turma->id}}">{{ $turma->oferecimento_id }}</a></li>
    @endforeach
</ul>
@endsection