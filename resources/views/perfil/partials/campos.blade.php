@php
    $erro = fn ($campo) => $errors->has($campo) ? ' is-invalid' : '';
    $valor = fn ($campo) => old($campo, $perfil->{$campo});
    $nasc = old('data_nascimento', $perfil->data_nascimento?->format('d/m/Y'));
    $semCpf = (bool) old('sem_cpf', $perfil->sem_cpf);
    $semRg = (bool) old('sem_rg', $perfil->sem_rg);
@endphp
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
