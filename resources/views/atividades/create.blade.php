@extends('layout')

@section('content')

<form method="POST" action="/atividades">
    @csrf

    <div class="mb-3">
        <label for="nome" class="form-label">Nome</label>
        <input type="text" class="form-control" id="nome" name="nome" value="{{ old('nome') }}" placeholder="Digite o nome da atividade">
    </div>

    <div class="mb-3">
        <label for="descricao" class="form-label">Descrição</label>
        <textarea class="form-control" id="descricao" name="descricao" rows="4" placeholder="Digite a descrição da atividade">{{ old('descricao') }}</textarea>
    </div>

    <button type="submit" class="btn btn-primary">Enviar</button>
</form>

@endsection