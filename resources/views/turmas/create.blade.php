@extends('layout')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm col-md-10 mx-auto p-0">
        <div class="card-header bg-white py-3">
            <h1 class="h4 mb-0 text-primary">Nova Turma</h1>
        </div>

        <form method="POST" action="/turmas">
            @csrf

            <div class="card-body">
                @include('turmas.partials.form')
            </div>

            <div class="card-footer bg-white text-right py-3">
                <a href="/turmas" class="btn btn-outline-secondary mr-2">Cancelar</a>
                <button type="submit" class="btn btn-primary">Enviar</button>
            </div>
        </form>
    </div>
</div>
@endsection
