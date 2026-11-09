@extends('layout')

@section('content')
@php($erro = fn ($campo) => $errors->has($campo) ? ' is-invalid' : '')
<div class="container py-4">
    <div class="card shadow-sm col-md-10 mx-auto p-0">
        <div class="card-header bg-white py-3">
            <h1 class="h4 mb-0 text-primary">Criar conta</h1>
        </div>

        <form method="POST" action="{{ route('cadastro.store') }}">
            @csrf
            <div class="card-body">
                <div class="alert alert-info">
                    Tem número USP? <a href="{{ route('login') }}" class="alert-link">Entre com a Senha Única</a>, não é preciso criar conta.
                </div>

                @if($errors->any())
                    <div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                @endif

                <h6 class="text-secondary font-weight-bold">Dados de acesso</h6>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="name">Nome completo</label>
                        <input type="text" class="form-control{{ $erro('name') }}" id="name" name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="email">E-mail</label>
                        <input type="email" class="form-control{{ $erro('email') }}" id="email" name="email" value="{{ old('email') }}" autocomplete="username" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="password">Senha</label>
                        <input type="password" class="form-control{{ $erro('password') }}" id="password" name="password" autocomplete="new-password" required>
                        <small class="form-text text-muted">Mínimo de 8 caracteres, com letras maiúsculas, minúsculas e números.</small>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="password_confirmation">Confirmar senha</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                    </div>
                </div>

                @include('perfil.partials.campos')
            </div>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
                <a href="{{ route('entrar') }}">Já tenho conta</a>
                <button type="submit" class="btn btn-primary">Criar conta</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('javascripts_bottom')
    @parent
    @include('perfil.partials.scripts')
@endsection
