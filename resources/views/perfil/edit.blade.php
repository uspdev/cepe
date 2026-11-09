@extends('layout')

@section('content')
@php
    $erro = fn ($campo) => $errors->has($campo) ? ' is-invalid' : '';
    $valor = fn ($campo) => old($campo, $perfil->{$campo});
    $action = $proprio ? '/perfil' : "/usuarios/{$alvo->id}/perfil";
    $admin = \Illuminate\Support\Facades\Gate::allows('perfis.gerenciar');
    $contaLocalEditavel = $alvo->local && ($proprio || \Illuminate\Support\Facades\Gate::allows('admin'));
@endphp
<div class="container py-4">
    <div class="card shadow-sm col-md-10 mx-auto p-0">
        <div class="card-header bg-white py-3">
            <h1 class="h4 mb-0 text-primary">{{ $proprio ? 'Meu perfil' : 'Perfil de '.$alvo->name }}</h1>
        </div>

        <form method="POST" action="{{ $action }}">
            @csrf
            @method('PATCH')
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                @endif

                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="credito">Crédito (R$)</label>
                        <input type="number" min="0" step="1" class="form-control{{ $erro('credito') }}" id="credito" name="credito" @disabled(! $admin) value="{{ $valor('credito') ?? 0 }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="vinculo_usp">Vínculo USP</label>
                        <input type="text" class="form-control{{ $erro('vinculo_usp') }}" id="vinculo_usp" name="vinculo_usp" @disabled(! $admin) value="{{ $valor('vinculo_usp') }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="unidade_usp">Unidade USP</label>
                        <input type="text" class="form-control{{ $erro('unidade_usp') }}" id="unidade_usp" name="unidade_usp" @disabled(! $admin) value="{{ $valor('unidade_usp') }}">
                    </div>
                </div>

                <h6 class="text-secondary font-weight-bold">Dados de acesso</h6>
                @if($contaLocalEditavel)
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="name">Nome completo</label>
                            <input type="text" class="form-control{{ $erro('name') }}" id="name" name="name" value="{{ old('name', $alvo->name) }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="email">E-mail</label>
                            <input type="email" class="form-control{{ $erro('email') }}" id="email" name="email" value="{{ old('email', $alvo->email) }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="password">Nova senha</label>
                            <input type="password" class="form-control{{ $erro('password') }}" id="password" name="password" autocomplete="new-password" placeholder="Somente se desejar mudar">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="password_confirmation">Confirmar nova senha</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                        </div>
                    </div>
                @else
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Nome completo</label>
                            <input type="text" class="form-control" value="{{ $alvo->name }}" disabled>
                        </div>
                        <div class="form-group col-md-6">
                            <label>E-mail</label>
                            <input type="text" class="form-control" value="{{ $alvo->email }}" disabled>
                        </div>
                    </div>
                    <p class="text-muted small">Nome e e-mail vêm da Senha Única USP e não podem ser alterados aqui.</p>
                @endif

                @include('perfil.partials.campos')
            </div>
            <div class="card-footer bg-white text-right py-3">
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>

    @php($carteiraUsuario = $proprio ? auth()->user()->carteiras()->latest('validade_fim')->first() : null)
    @if($proprio && ! $perfil->numeroCepeuspVigente())
        <div class="card shadow-sm col-md-10 mx-auto p-0 mt-4">
            <div class="card-header bg-white py-3"><h2 class="h5 mb-0">Tenho Carteira CEPEUSP</h2></div>
            <form method="POST" action="/perfil/carteira" class="card-body">
                @csrf
                <p class="text-muted small">
                    Dependentes com carteira ativa (solicitada pelo titular) informam aqui o número para se inscrever nos cursos.
                    O CPF, ou o nome e a data de nascimento, do perfil devem ser os mesmos da solicitação.
                    @if($carteiraUsuario?->numero)<br>Sua carteira nº {{ $carteiraUsuario->numero }} está com status "{{ $carteiraUsuario->status_nome }}"{{ $carteiraUsuario->validade_fim ? ' e validade até '.$carteiraUsuario->validade_fim->format('d/m/Y') : '' }}.@endif
                </p>
                <div class="form-inline">
                    <label for="numero_carteira" class="mr-2">Número da carteira</label>
                    <input type="text" id="numero_carteira" name="numero" class="form-control mr-2{{ $errors->has('numero') ? ' is-invalid' : '' }}" value="{{ old('numero') }}">
                    <button type="submit" class="btn btn-outline-primary">Validar</button>
                </div>
                @error('numero')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </form>
        </div>
    @endif
</div>
@endsection

@section('javascripts_bottom')
    @parent
    @include('perfil.partials.scripts')
@endsection
