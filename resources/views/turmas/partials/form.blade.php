@php
    $turma = $turma ?? null;

    // identifica a qual formulário da página pertence o old() após falha de validação
    $formKey = $turma && $turma->exists ? 'turma-' . $turma->id : 'turma-nova';
    $ativo = old('form_key') === $formKey;

    $valor = function ($campo, $default = null) use ($ativo, $turma) {
        $atual = $turma?->{$campo} ?? $default;
        return $ativo ? old($campo, $atual) : $atual;
    };

    $marcados = function ($campo) use ($ativo, $turma) {
        $atual = (array) ($turma?->{$campo} ?? []);
        return (array) ($ativo ? old($campo, $atual) : $atual);
    };

    $hora = fn($campo) => substr((string) $valor($campo), 0, 5);
    $invalido = fn($campo) => $ativo && $errors->has($campo) ? ' is-invalid' : '';

    $sexos = $marcados('sexo');
    $dias = $marcados('dias_semana');
    $diasSemana = [
        'segunda' => 'Seg', 'terca' => 'Ter', 'quarta' => 'Qua',
        'quinta' => 'Qui', 'sexta' => 'Sex', 'sabado' => 'Sáb', 'domingo' => 'Dom',
    ];

    $taxasSalvas = $turma?->taxas->keyBy(fn($taxa) => "{$taxa->perfil}-{$taxa->periodo_inscricao}") ?? collect();
    $valorTaxa = function ($perfil, $periodo) use ($ativo, $taxasSalvas) {
        $key = "{$perfil}-{$periodo}";
        $atual = $taxasSalvas->get($key)?->valor;
        return $ativo ? old("taxas.{$perfil}.{$periodo}.valor", $atual) : $atual;
    };

    $configVagas = $turma?->exists
        ? $turma->configuracaoVagas()
        : \App\Models\VagaTurma::configuracao(isset($oferecimento) ? $oferecimento->atividade?->tipo : null);
    $perfisVagas = collect($configVagas['perfis'])->mapWithKeys(fn($perfil) => [$perfil => config('cepe.perfil.' . $perfil)]);
    if ($configVagas['reservadas']) {
        $perfisVagas[\App\Models\VagaTurma::RESERVADA] = 'Reservadas';
    }

    $vagasSalvas = $turma?->vagas->keyBy(fn($vaga) => "{$vaga->perfil}-{$vaga->tamanho}") ?? collect();
    $valorVaga = function ($perfil, $tamanho) use ($ativo, $vagasSalvas) {
        $atual = $vagasSalvas->get("{$perfil}-{$tamanho}")?->quantidade;
        return $ativo ? old('vagas.' . $perfil . ($tamanho === '' ? '' : ".{$tamanho}"), $atual) : $atual;
    };
@endphp

<input type="hidden" name="form_key" value="{{ $formKey }}">

@if($ativo && $errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 pl-3">
            @foreach($errors->all() as $erro)
                <li>{{ $erro }}</li>
            @endforeach
        </ul>
    </div>
@endif

@isset($oferecimento)
    <input type="hidden" name="oferecimento_id" value="{{ $oferecimento->id }}">
@else
    <div class="form-group">
        <label for="{{ $formKey }}-oferecimento_id">Oferecimento</label>
        <select class="form-control{{ $invalido('oferecimento_id') }}" id="{{ $formKey }}-oferecimento_id" name="oferecimento_id">
            <option value="">Selecione</option>
            @foreach($oferecimentos as $op)
                <option value="{{ $op->id }}" {{ $valor('oferecimento_id') == $op->id ? 'selected' : '' }}>
                    Oferecimento {{ $op->id }}{{ $op->atividade ? ' - ' . $op->atividade->nome : '' }}
                </option>
            @endforeach
        </select>
    </div>
@endisset

<div class="form-group">
    <label class="d-block">Sexo</label>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" id="{{ $formKey }}-sexo_m" name="sexo[]" value="m" {{ in_array('m', $sexos) ? 'checked' : '' }}>
        <label class="form-check-label" for="{{ $formKey }}-sexo_m">Masculino</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" id="{{ $formKey }}-sexo_f" name="sexo[]" value="f" {{ in_array('f', $sexos) ? 'checked' : '' }}>
        <label class="form-check-label" for="{{ $formKey }}-sexo_f">Feminino</label>
    </div>
</div>

<div class="form-group">
    <label class="d-block">Dias da Semana</label>
    @foreach($diasSemana as $dia => $rotulo)
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" id="{{ $formKey }}-dia_{{ $dia }}" name="dias_semana[]" value="{{ $dia }}" {{ in_array($dia, $dias) ? 'checked' : '' }}>
            <label class="form-check-label" for="{{ $formKey }}-dia_{{ $dia }}">{{ $rotulo }}</label>
        </div>
    @endforeach
</div>

<div class="form-row">
    <div class="form-group col-md-3">
        <label for="{{ $formKey }}-horario_inicio">Horário de Início</label>
        <input type="time" class="form-control{{ $invalido('horario_inicio') }}" id="{{ $formKey }}-horario_inicio" name="horario_inicio" value="{{ $hora('horario_inicio') }}">
    </div>
    <div class="form-group col-md-3">
        <label for="{{ $formKey }}-horario_fim">Horário de Fim</label>
        <input type="time" class="form-control{{ $invalido('horario_fim') }}" id="{{ $formKey }}-horario_fim" name="horario_fim" value="{{ $hora('horario_fim') }}">
    </div>
</div>

<div class="form-group">
    <label for="{{ $formKey }}-professor">Professor(a)</label>
    <input type="text" class="form-control{{ $invalido('professor') }}" id="{{ $formKey }}-professor" name="professor" value="{{ $valor('professor') }}">
</div>

<div class="form-row">
    <div class="form-group col-md-3">
        <label for="{{ $formKey }}-idade_minima">Idade Mínima</label>
        <input type="number" class="form-control{{ $invalido('idade_minima') }}" id="{{ $formKey }}-idade_minima" name="idade_minima" min="1" step="1" value="{{ $valor('idade_minima') }}">
    </div>
    <div class="form-group col-md-3">
        <label for="{{ $formKey }}-idade_maxima">Idade Máxima</label>
        <input type="number" class="form-control{{ $invalido('idade_maxima') }}" id="{{ $formKey }}-idade_maxima" name="idade_maxima" min="1" step="1" value="{{ $valor('idade_maxima') }}">
    </div>
    <div class="form-group col-md-3">
        <label for="{{ $formKey }}-nivel">Nível</label>
        <input type="text" class="form-control{{ $invalido('nivel') }}" id="{{ $formKey }}-nivel" name="nivel" value="{{ $valor('nivel') }}">
    </div>
    <div class="form-group col-md-3">
        <label for="{{ $formKey }}-local">Local</label>
        <input type="text" class="form-control{{ $invalido('local') }}" id="{{ $formKey }}-local" name="local" value="{{ $valor('local') }}">
    </div>
</div>

@if($configVagas['ano_nascimento'])
    <div class="form-row">
        <div class="form-group col-md-3">
            <label for="{{ $formKey }}-ano_nascimento_minimo">Nascidos a partir de (ano)</label>
            <input type="number" class="form-control{{ $invalido('ano_nascimento_minimo') }}" id="{{ $formKey }}-ano_nascimento_minimo" name="ano_nascimento_minimo" min="1900" max="2100" step="1" value="{{ $valor('ano_nascimento_minimo') }}">
        </div>
        <div class="form-group col-md-3">
            <label for="{{ $formKey }}-ano_nascimento_maximo">Nascidos até (ano)</label>
            <input type="number" class="form-control{{ $invalido('ano_nascimento_maximo') }}" id="{{ $formKey }}-ano_nascimento_maximo" name="ano_nascimento_maximo" min="1900" max="2100" step="1" value="{{ $valor('ano_nascimento_maximo') }}">
        </div>
    </div>
@endif

<h6 class="text-secondary font-weight-bold mt-4">Vagas{{ $configVagas['tamanhos'] ? ' por Tamanho de Camiseta' : '' }}</h6>
@if($configVagas['tamanhos'])
    <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th></th>
                    @foreach($configVagas['tamanhos'] as $tamanho => $rotuloTamanho)
                        <th class="text-center">{{ $rotuloTamanho }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($perfisVagas as $perfil => $label)
                    <tr>
                        <td class="align-middle">{{ $label }}</td>
                        @foreach($configVagas['tamanhos'] as $tamanho => $rotuloTamanho)
                            <td>
                                <input type="number" class="form-control form-control-sm{{ $invalido("vagas.{$perfil}.{$tamanho}") }}" aria-label="{{ $label }} - {{ $rotuloTamanho }}" name="vagas[{{ $perfil }}][{{ $tamanho }}]" min="0" step="1" value="{{ $valorVaga($perfil, $tamanho) }}">
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="form-row">
        @foreach($perfisVagas as $perfil => $label)
            <div class="form-group col-md">
                <label for="{{ $formKey }}-vagas_{{ $perfil }}">{{ $label }}</label>
                <input type="number" class="form-control{{ $invalido('vagas.' . $perfil) }}" id="{{ $formKey }}-vagas_{{ $perfil }}" name="vagas[{{ $perfil }}]" min="0" step="1" value="{{ $valorVaga($perfil, '') }}">
            </div>
        @endforeach
    </div>
@endif

<div class="form-group">
    <label for="{{ $formKey }}-observacoes">Observações</label>
    <textarea class="form-control{{ $invalido('observacoes') }}" id="{{ $formKey }}-observacoes" name="observacoes" rows="3">{{ $valor('observacoes') }}</textarea>
</div>

@foreach([1 => 'Primeiro', 2 => 'Segundo'] as $periodo => $tituloPeriodo)
    <h6 class="text-secondary font-weight-bold mt-{{ $periodo === 1 ? '4' : '3' }}">Taxas do {{ $tituloPeriodo }} Período de Inscrição</h6>
    <div class="form-row">
        @foreach(config('cepe.perfil') as $perfil => $label)
            @php $taxaIndex = "{$perfil}_{$periodo}"; @endphp
            <div class="form-group col-md">
                <input type="hidden" name="taxas[{{ $taxaIndex }}][perfil]" value="{{ $perfil }}">
                <input type="hidden" name="taxas[{{ $taxaIndex }}][periodo_inscricao]" value="{{ $periodo }}">
                <label for="{{ $formKey }}-taxa_{{ $perfil }}_{{ $periodo }}">{{ $label }}</label>
                <input type="number" class="form-control{{ $invalido('taxas.' . $taxaIndex . '.valor') }}" id="{{ $formKey }}-taxa_{{ $perfil }}_{{ $periodo }}" name="taxas[{{ $taxaIndex }}][valor]" min="0" step="0.01" value="{{ $valorTaxa($perfil, $periodo) }}">
            </div>
        @endforeach
    </div>
@endforeach

<div class="form-group">
    <label for="{{ $formKey }}-info_contato">Informações para Contato</label>
    <textarea class="form-control{{ $invalido('info_contato') }}" id="{{ $formKey }}-info_contato" name="info_contato" rows="3">{{ $valor('info_contato') }}</textarea>
</div>

<div class="form-group">
    <label for="{{ $formKey }}-declaracao">Declaração do Usuário</label>
    <textarea class="form-control{{ $invalido('declaracao') }}" id="{{ $formKey }}-declaracao" name="declaracao" rows="3">{{ $valor('declaracao') }}</textarea>
</div>
