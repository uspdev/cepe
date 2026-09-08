@extends('layout')

@section('content')
<a href="/oferecimentos/create" class="btn btn-success">Cadastrar novo oferecimento</a><br><br>
<form class="form">
    <input type="text" name="search" value="{{ request('search') }}">
    <button type="submit">Pesquisar</button>
</form>

<h1>Listagem de Oferecimentos</h1>
<ul>
    @foreach($oferecimentos as $oferecimento)
        <li><a href="/oferecimentos/{{ $oferecimento->id}}">{{ $oferecimento->atividade_id }}</a></li>
    @endforeach
</ul>
@endsection