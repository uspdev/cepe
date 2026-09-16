@extends('layout')

@section('content')

<form method="POST" action="/matriculas">
    @csrf
    A: <input type="text" name="a" value="{{old('a')}}">
    <button type="submit">Enviar</button>
</form>

@endsection