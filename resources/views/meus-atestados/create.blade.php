@extends('layout')

@section('content')
@php
    $tipo = old('tipo', request('tipo', 'atestado_medico'));
    abort_unless(isset(config('cepe.atestados.tipos')[$tipo]), 404);
    $tipo = isset($tipos[$tipo]) ? $tipo : 'atestado_medico';
    $perguntas = config('cepe.atestados.parq.perguntas');
@endphp
<div class="container py-4">
    <div class="card shadow-sm col-md-10 mx-auto p-0">
        <div class="card-header bg-white py-3"><h1 class="h4 mb-0 text-primary">Enviar Atestado</h1></div>

        <form method="POST" action="/meus-atestados" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div>
                @endif

                <ul class="nav nav-pills mb-4">
                    @foreach($tipos as $key => $nome)
                        <li class="nav-item"><a class="nav-link {{ $tipo === $key ? 'active' : '' }}" href="/meus-atestados/create?tipo={{ $key }}">{{ $nome }}</a></li>
                    @endforeach
                </ul>

                @if($tipo === 'parq' && ! auth()->user()->perfil?->data_nascimento)
                    <div class="alert alert-warning">Para preencher o PAR-Q é necessário informar sua data de nascimento. <a href="/perfil" class="alert-link">Atualizar perfil</a></div>
                @endif

                <input type="hidden" name="tipo" value="{{ $tipo }}">
                @if($tipo !== 'parq')
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="emitido_em">Data de emissão</label>
                        <input type="text" class="form-control datepicker" id="emitido_em" name="emitido_em" value="{{ old('emitido_em') }}" placeholder="dd/mm/aaaa">
                    </div>
                </div>
                @endif

                @if($tipo !== 'parq')
                <div id="bloco-arquivo" class="form-group">
                    <label for="arquivo">Arquivo (PDF, JPG ou PNG, até {{ config('cepe.atestados.arquivo.max_kb') / 1024 }} MB)</label>
                    <input type="file" class="form-control-file" id="arquivo" name="arquivo" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                @else
                <div id="bloco-parq">
                    <p class="text-muted">Este questionário identifica a necessidade de avaliação médica antes do início da atividade física. Responda "sim" ou "não" a cada pergunta.</p>
                    @foreach($perguntas as $n => $texto)
                        <div class="form-group">
                            <label class="d-block">{{ $n }}) {{ $texto }}</label>
                            @foreach(['sim' => 'Sim', 'nao' => 'Não'] as $v => $r)
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input parq-resp" type="radio" id="resp_{{ $n }}_{{ $v }}" name="respostas[{{ $n }}]" value="{{ $v }}" @checked(old("respostas.$n") === $v)>
                                    <label class="form-check-label" for="resp_{{ $n }}_{{ $v }}">{{ $r }}</label>
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                    <div id="bloco-termo" class="form-group border rounded p-3 bg-light">
                        <h6>Termo de Responsabilidade para Prática de Atividade Física</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="termo_aceito" name="termo_aceito" value="1" @checked(old('termo_aceito'))>
                            <label class="form-check-label" for="termo_aceito">{{ config('cepe.atestados.parq.termo') }}</label>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            <div class="card-footer bg-white text-right py-3">
                <a href="/meus-atestados" class="btn btn-outline-secondary mr-2">Cancelar</a>
                <button type="submit" class="btn btn-primary">Enviar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('javascripts_bottom')
    @parent
    <script>
        $(function () {
            function atualizar() {
                $('#bloco-termo').toggle($('.parq-resp[value=sim]:checked').length > 0);
            }
            $('.parq-resp').on('change', atualizar);
            atualizar();
        });
    </script>
@endsection
