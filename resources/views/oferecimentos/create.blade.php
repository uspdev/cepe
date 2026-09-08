@extends('layout')

@section('content')

<form method="POST" action="/oferecimentos">
    @csrf
    Atividade_id: <input type="text" name="atividade_id" value="{{old('atividade_id')}}">
    <button type="submit">Enviar</button>
</form>

@endsection