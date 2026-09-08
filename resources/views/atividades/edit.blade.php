@extends('layout')

@section('content')

<form method="POST" action="/atividades/{{ $atividade->id }}">
    @csrf
    @method('PATCH')
    Nome: <input type="text" name="nome" value="{{ old('nome', $atividade->nome) }}">
    Descricao: <input type="text" name="descricao" value="{{ old('descricao', $atividade->descricao) }}">
    <button type="submit">Enviar</button>
</form>
@endsection