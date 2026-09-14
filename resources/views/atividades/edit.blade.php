@extends('layout')

@section('content')

<form method="POST" action="/atividades/{{ $atividade->id }}">
    @csrf
    @method('PATCH')

    <div class="mb-3">
        <label for="nome" class="form-label">Nome</label>
        <input type="text" class="form-control" id="nome" name="nome" value="{{ old('nome', $atividade->nome) }}" placeholder="Digite o nome da atividade">
    </div>

    <div class="mb-3">
        <label for="descricao" class="form-label">Descrição</label>
        <textarea class="form-control" id="descricao" name="descricao" rows="4" placeholder="Digite a descrição da atividade">{{ old('descricao', $atividade->descricao) }}</textarea>
    </div>

    <div class="mb-3">
        <label for="tipo" class="form-label">Tipo</label>
        <select class="form-control" id="tipo" name="tipo">
            @foreach(config('cepe.tipos_inscricao') as $key => $value)
                <option value="{{ $key }}" @selected(old('tipo', $atividade->tipo) == $key)>{{ $value }}</option>
            @endforeach
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Enviar</button>
</form>

@endsection
