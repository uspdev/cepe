@extends('layout')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm col-md-10 mx-auto p-0">
        <div class="card-header bg-white py-3">
            <h1 class="h4 mb-0 text-primary">Entrar</h1>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 border-right mb-4 mb-md-0">
                    <h2 class="h5">Comunidade USP</h2>
                    <p class="text-muted">Alunos, docentes e servidores com número USP entram com a Senha Única.</p>
                    <a href="{{ route('login') }}" class="btn btn-primary btn-block">
                        <i class="fas fa-key"></i> Entrar com Senha Única
                    </a>
                </div>
                <div class="col-md-6">
                    <h2 class="h5">Comunidade externa</h2>
                    @if($errors->any())
                        <div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                    @endif
                    <form method="POST" action="{{ route('entrar.store') }}">
                        @csrf
                        <div class="form-group">
                            <label for="email">E-mail</label>
                            <input type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" id="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                        </div>
                        <div class="form-group">
                            <label for="password">Senha</label>
                            <input type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" id="password" name="password" autocomplete="current-password" required>
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1" @checked(old('remember'))>
                            <label class="form-check-label" for="remember">Lembrar-me</label>
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-block">Entrar</button>
                    </form>
                    <p class="mt-3 mb-0 text-center">
                        Não tem conta? <a href="{{ route('cadastro') }}">Cadastre-se</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
