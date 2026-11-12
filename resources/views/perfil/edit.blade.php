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

    <div class="card shadow-sm col-md-10 mx-auto p-0 mt-4">
        <div class="card-header bg-white py-3"><h2 class="h5 mb-0">Carteira CEPEUSP</h2></div>
        <div class="card-body">
            @if($carteiraVigente)
                <p class="mb-0">
                    Número <strong>{{ $carteiraVigente->numero }}</strong>
                    — {{ $carteiraVigente->status_nome }}
                    @if($carteiraVigente->validade_fim)
                        — válida até {{ $carteiraVigente->validade_fim->format('d/m/Y') }}
                    @endif
                </p>
            @else
                <p class="text-muted mb-0">Não há carteira vigente vinculada a este perfil.</p>
            @endif
        </div>
    </div>

    @if($podeVincularCarteiras)
        <div class="card shadow-sm col-md-10 mx-auto p-0 mt-4">
            <div class="card-header bg-white py-3"><h2 class="h5 mb-0">Gerenciar vínculo de carteiras</h2></div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                @endif

                @forelse($carteirasVinculadas as $carteira)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <span>{{ $carteira->numero }} — {{ $carteira->categoria_nome }} — {{ $carteira->status_nome }}</span>
                        <form method="POST" action="/usuarios/{{ $alvo->id }}/carteiras/{{ $carteira->id }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Desvincular</button>
                        </form>
                    </div>
                @empty
                    <p class="text-muted">Nenhuma carteira vinculada.</p>
                @endforelse

                @if($carteirasDisponiveis->isNotEmpty())
                    <form method="POST" action="/usuarios/{{ $alvo->id }}/carteiras/vincular" class="mt-3">
                        @csrf
                        <div class="form-row align-items-end">
                            <div class="form-group col-md-8">
                                <label for="carteira_id">Vincular carteira já cadastrada</label>
                                <select class="form-control" id="carteira_id" name="carteira_id" required>
                                    <option value="">Selecione</option>
                                    @foreach($carteirasDisponiveis as $carteira)
                                        <option value="{{ $carteira->id }}">{{ $carteira->numero }} — {{ $carteira->categoria_nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-4"><button type="submit" class="btn btn-outline-primary">Vincular</button></div>
                        </div>
                    </form>
                @endif

                <hr>
                <h3 class="h6">Criar carteira e vincular</h3>
                <form method="POST" action="/usuarios/{{ $alvo->id }}/carteiras">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="numero">Número</label>
                            <input type="text" class="form-control" id="numero" name="numero" value="{{ old('numero') }}" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="categoria">Categoria</label>
                            <select class="form-control" id="categoria" name="categoria" required>
                                @foreach(config('cepe.carteiras.categorias') as $chave => $categoria)
                                    <option value="{{ $chave }}" @selected(old('categoria') === $chave)>{{ $categoria['rotulo'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status" required>
                                @foreach(\App\Models\Carteira::STATUS_GERENCIAVEIS as $status)
                                    <option value="{{ $status }}" @selected(old('status', 'ativa') === $status)>{{ config('cepe.carteiras.status.'.$status, $status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="parentesco">Parentesco</label>
                            <select class="form-control" id="parentesco" name="parentesco">
                                <option value="">—</option>
                                @foreach(config('cepe.carteiras.parentescos') as $chave => $nome)
                                    <option value="{{ $chave }}" @selected(old('parentesco') === $chave)>{{ $nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="via">Via</label>
                            <input type="number" min="1" class="form-control" id="via" name="via" value="{{ old('via', 1) }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="validade_inicio">Início da validade</label>
                            <input type="date" class="form-control" id="validade_inicio" name="validade_inicio" value="{{ old('validade_inicio') }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="validade_fim">Fim da validade</label>
                            <input type="date" class="form-control" id="validade_fim" name="validade_fim" value="{{ old('validade_fim') }}" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Criar e vincular</button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection

@section('javascripts_bottom')
    @parent
    @include('perfil.partials.scripts')
@endsection
