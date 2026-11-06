@extends('layout')

@section('content')
@php
    $rotulos = config('cepe.carteiras.documentos');
    $dependente = $config['beneficiario'] === 'dependente';
    $arquivo = config('cepe.carteiras.arquivo');
    $campoArquivo = function ($tipo) use ($errors) {
        $foto = $tipo === 'foto';
        return [
            'accept' => $foto ? '.jpg,.jpeg' : '.pdf,.jpg,.jpeg,.png',
            'erro' => $errors->has("documentos.{$tipo}") ? ' is-invalid' : '',
        ];
    };
@endphp
<div class="container py-4">
    <div class="card shadow-sm col-md-10 mx-auto p-0">
        <div class="card-header bg-white py-3">
            <h1 class="h4 mb-0 text-primary">Solicitar Carteira CEPEUSP</h1>
        </div>

        <form method="POST" action="/minhas-carteiras" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div>
                @endif

                <input type="hidden" name="categoria" value="{{ $categoria }}">
                <ul class="nav nav-pills mb-3">
                    @foreach($categorias as $chave => $cat)
                        <li class="nav-item"><a class="nav-link {{ $categoria === $chave ? 'active' : '' }}" href="/minhas-carteiras/create?categoria={{ $chave }}">{{ $cat['rotulo'] }}</a></li>
                    @endforeach
                </ul>

                @php($taxa = \App\Models\CarteiraTaxa::descricao($categoria))
                @if($taxa)
                    <div class="alert alert-info small">
                        {{ $taxa }}. O pagamento é orientado pela SRU após a análise dos documentos.
                    </div>
                @endif

                @if($dependente)
                    <h6 class="text-secondary font-weight-bold">Dependente</h6>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="nome">Nome completo</label>
                            <input type="text" class="form-control{{ $errors->has('nome') ? ' is-invalid' : '' }}" id="nome" name="nome" value="{{ old('nome') }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="cpf">CPF</label>
                            <input type="text" class="form-control{{ $errors->has('cpf') ? ' is-invalid' : '' }}" id="cpf" name="cpf" value="{{ old('cpf') }}" placeholder="000.000.000-00">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="data_nascimento">Data de nascimento</label>
                            <input type="text" class="form-control datepicker{{ $errors->has('data_nascimento') ? ' is-invalid' : '' }}" id="data_nascimento" name="data_nascimento" value="{{ old('data_nascimento') }}" placeholder="dd/mm/aaaa">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="parentesco">Parentesco com o titular</label>
                        <select class="form-control{{ $errors->has('parentesco') ? ' is-invalid' : '' }}" id="parentesco" name="parentesco">
                            <option value="">Selecione</option>
                            @foreach(array_keys($config['parentescos']) as $parentesco)
                                <option value="{{ $parentesco }}" @selected(old('parentesco') === $parentesco)>{{ config('cepe.carteiras.parentescos.'.$parentesco) }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Ex-cônjuge não é considerado dependente.</small>
                    </div>
                @else
                    <p class="text-muted">A carteira será emitida em nome de <strong>{{ auth()->user()->name }}</strong>, com os dados do seu <a href="/perfil">perfil</a>.</p>
                @endif

                <h6 class="text-secondary font-weight-bold mt-3">Documentos</h6>
                <p class="text-muted small">PDF, JPG ou PNG até {{ $arquivo['max_kb'] / 1024 }} MB. A foto deve ser 3x4, recente, em JPG.</p>

                @foreach($comuns as $tipo)
                    @php($campo = $campoArquivo($tipo))
                    <div class="form-group">
                        <label for="doc_{{ $tipo }}">{{ $rotulos[$tipo] }}</label>
                        <input type="file" class="form-control-file{{ $campo['erro'] }}" id="doc_{{ $tipo }}" name="documentos[{{ $tipo }}]" accept="{{ $campo['accept'] }}">
                    </div>
                @endforeach

                @foreach($porParentesco as $parentesco => $tipos)
                    <div class="docs-parentesco" data-parentesco="{{ $parentesco }}">
                        @foreach($tipos as $tipo)
                            @php($campo = $campoArquivo($tipo))
                            <div class="form-group">
                                <label for="doc_{{ $parentesco }}_{{ $tipo }}">{{ $rotulos[$tipo] }}</label>
                                @if($tipo === 'declaracao_uniao_estavel')
                                    <small class="d-block"><a href="https://cepe.usp.br/wp-content/uploads/DECLARACAO-uniao-estavel.pdf" target="_blank" rel="noopener">Modelo CEPEUSP de declaração</a></small>
                                @endif
                                <input type="file" class="form-control-file{{ $campo['erro'] }}" id="doc_{{ $parentesco }}_{{ $tipo }}" name="documentos[{{ $tipo }}]" accept="{{ $campo['accept'] }}" disabled>
                            </div>
                        @endforeach
                    </div>
                @endforeach

                @foreach($opcionais as $tipo)
                    @php($campo = $campoArquivo($tipo))
                    <div class="form-group">
                        <label for="doc_{{ $tipo }}">{{ $rotulos[$tipo] }} <small class="text-muted">(quando houver)</small></label>
                        <input type="file" class="form-control-file{{ $campo['erro'] }}" id="doc_{{ $tipo }}" name="documentos[{{ $tipo }}]" accept="{{ $campo['accept'] }}">
                    </div>
                @endforeach
            </div>
            <div class="card-footer bg-white text-right py-3">
                <a href="/minhas-carteiras" class="btn btn-outline-secondary mr-2">Cancelar</a>
                <button type="submit" class="btn btn-primary">Enviar solicitação</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('javascripts_bottom')
    @parent
    <script>
        $(function () {
            // mostra só os documentos do parentesco escolhido; os ocultos ficam desabilitados para não serem enviados em duplicidade
            function atualizar() {
                var escolhido = $('#parentesco').val();
                $('.docs-parentesco').each(function () {
                    var ativo = $(this).data('parentesco') === escolhido;
                    $(this).toggle(ativo).find('input').prop('disabled', !ativo);
                });
            }
            $('#parentesco').on('change', atualizar);
            atualizar();
        });
    </script>
@endsection
