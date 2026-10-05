@extends('layout')

@section('content')
@php
    $erro = fn ($campo) => $errors->has($campo) ? ' is-invalid' : '';
    $valor = fn ($campo) => old($campo, $perfil->{$campo});
    $nasc = old('data_nascimento', $perfil->data_nascimento?->format('d/m/Y'));
    $action = $proprio ? '/perfil' : "/usuarios/{$alvo->id}/perfil";
    $admin = \Illuminate\Support\Facades\Gate::allows('admin');
    $semCpf = (bool) old('sem_cpf', $perfil->sem_cpf);
    $semRg = (bool) old('sem_rg', $perfil->sem_rg);
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
                        <label for="numero_cepeusp">Número CEPEUSP</label>
                        <input type="text" class="form-control{{ $erro('numero_cepeusp') }}" id="numero_cepeusp" name="numero_cepeusp" @disabled(! $admin) value="{{ $valor('numero_cepeusp') }}">
                    </div>
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
                @if($alvo->local)
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

                <h6 class="text-secondary font-weight-bold mt-4">Identificação</h6>
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label class="d-block">Sexo</label>
                        @foreach(['m' => 'Masculino', 'f' => 'Feminino'] as $k => $r)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input{{ $erro('sexo') }}" type="radio" id="sexo_{{ $k }}" name="sexo" value="{{ $k }}" @checked($valor('sexo') === $k)>
                                <label class="form-check-label" for="sexo_{{ $k }}">{{ $r }}</label>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-group col-md-3">
                        <label for="data_nascimento">Data de nascimento</label>
                        <input type="text" class="form-control datepicker{{ $erro('data_nascimento') }}" id="data_nascimento" name="data_nascimento" value="{{ $nasc }}" placeholder="dd/mm/aaaa">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="cpf">CPF</label>
                        <input type="text" class="form-control{{ $erro('cpf') }}" id="cpf" name="cpf" value="{{ old('cpf', $perfil->cpf) }}" placeholder="000.000.000-00" @disabled($semCpf)>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" id="sem_cpf" name="sem_cpf" value="1" @checked($semCpf)>
                            <label class="form-check-label" for="sem_cpf">Não tenho CPF</label>
                        </div>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="rg">RG</label>
                        <input type="text" class="form-control{{ $erro('rg') }}" id="rg" name="rg" value="{{ $valor('rg') }}" @disabled($semRg)>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" id="sem_rg" name="sem_rg" value="1" @checked($semRg)>
                            <label class="form-check-label" for="sem_rg">Não tenho RG</label>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="passaporte">Passaporte (se estrangeiro)</label>
                        <input type="text" class="form-control{{ $erro('passaporte') }}" id="passaporte" name="passaporte" value="{{ $valor('passaporte') }}">
                    </div>
                </div>

                <h6 class="text-secondary font-weight-bold mt-4">Contato e emergência</h6>
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="telefone">Telefone</label>
                        <input type="tel" class="form-control tel{{ $erro('telefone') }}" id="telefone" name="telefone" value="{{ $valor('telefone') }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="celular">Celular</label>
                        <input type="tel" class="form-control tel{{ $erro('celular') }}" id="celular" name="celular" value="{{ $valor('celular') }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="emergencia_nome">Contato de emergência</label>
                        <input type="text" class="form-control{{ $erro('emergencia_nome') }}" id="emergencia_nome" name="emergencia_nome" value="{{ $valor('emergencia_nome') }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="emergencia_telefone">Telefone de emergência</label>
                        <input type="tel" class="form-control tel{{ $erro('emergencia_telefone') }}" id="emergencia_telefone" name="emergencia_telefone" value="{{ $valor('emergencia_telefone') }}">
                    </div>
                </div>

                <h6 class="text-secondary font-weight-bold mt-4">Endereço</h6>
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="cep">CEP</label>
                        <input type="text" class="form-control{{ $erro('cep') }}" id="cep" name="cep" value="{{ $valor('cep') }}" placeholder="00000-000">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="estado">Estado</label>
                        <select class="form-control{{ $erro('estado') }}" id="estado" name="estado">
                            <option value="">Selecione</option>
                            @foreach(config('cepe.estados') as $uf => $nome)
                                <option value="{{ $uf }}" @selected($valor('estado') === $uf)>{{ $nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="cidade">Cidade</label>
                        <input type="text" class="form-control{{ $erro('cidade') }}" id="cidade" name="cidade" value="{{ $valor('cidade') }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="bairro">Bairro</label>
                        <input type="text" class="form-control{{ $erro('bairro') }}" id="bairro" name="bairro" value="{{ $valor('bairro') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label for="endereco">Endereço</label>
                    <input type="text" class="form-control{{ $erro('endereco') }}" id="endereco" name="endereco" value="{{ $valor('endereco') }}" placeholder="Logradouro, número - complemento">
                </div>

                <h6 class="text-secondary font-weight-bold mt-4">Perfil esportivo e profissional</h6>
                <div class="form-row">
                    @foreach(['faixa_grau' => 'Faixa ou grau esportivo', 'associacao' => 'Associação esportiva', 'formacao' => 'Formação escolar ou universitária', 'instituicao' => 'Instituição', 'profissao' => 'Profissão, cargo ou função', 'organizacao' => 'Organização ou empresa'] as $nome => $rotulo)
                        <div class="form-group col-md-6">
                            <label for="{{ $nome }}">{{ $rotulo }}</label>
                            <input type="text" class="form-control{{ $erro($nome) }}" id="{{ $nome }}" name="{{ $nome }}" value="{{ $valor($nome) }}">
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer bg-white text-right py-3">
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('javascripts_bottom')
    @parent
    <script>
        $(function () {
            function digitos(v) { return (v || '').replace(/\D/g, ''); }
            function mascCpf(v) {
                v = digitos(v).slice(0, 11);
                return v.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            }
            function mascTel(v) {
                v = digitos(v).slice(0, 11);
                return v.length > 10 ? v.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3') : v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3').replace(/-$/, '');
            }
            function mascCep(v) { return digitos(v).slice(0, 8).replace(/(\d{5})(\d)/, '$1-$2'); }

            $('#cpf').on('input', function () { this.value = mascCpf(this.value); }).trigger('input');
            $('.tel').on('input', function () { this.value = mascTel(this.value); }).trigger('input');
            $('#cep').on('input', function () { this.value = mascCep(this.value); }).trigger('input');

            $('#sem_cpf').on('change', function () { $('#cpf').prop('disabled', this.checked).val(this.checked ? '' : $('#cpf').val()); });
            $('#sem_rg').on('change', function () { $('#rg').prop('disabled', this.checked).val(this.checked ? '' : $('#rg').val()); });

            $('#cep').on('blur', function () {
                var cep = digitos(this.value);
                if (cep.length !== 8) { return; }
                $.getJSON('https://viacep.com.br/ws/' + cep + '/json/').done(function (d) {
                    if (d.erro) { return; }
                    if (!$('#estado').val()) { $('#estado').val(d.uf); }
                    if (!$('#cidade').val()) { $('#cidade').val(d.localidade); }
                    if (!$('#bairro').val()) { $('#bairro').val(d.bairro); }
                    if (!$('#endereco').val()) { $('#endereco').val(d.logradouro); }
                });
            });
        });
    </script>
@endsection
