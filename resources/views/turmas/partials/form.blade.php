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

<div class="form-row">
    <div class="form-group col-md-4">
        <label for="{{ $formKey }}-vagas_usp">Vagas Comunidade USP</label>
        <input type="number" class="form-control{{ $invalido('vagas_usp') }}" id="{{ $formKey }}-vagas_usp" name="vagas_usp" min="0" step="1" value="{{ $valor('vagas_usp') }}">
    </div>
    <div class="form-group col-md-4">
        <label for="{{ $formKey }}-vagas_papfe">Vagas PAPFE</label>
        <input type="number" class="form-control{{ $invalido('vagas_papfe') }}" id="{{ $formKey }}-vagas_papfe" name="vagas_papfe" min="0" step="1" value="{{ $valor('vagas_papfe') }}">
    </div>
    <div class="form-group col-md-4">
        <label for="{{ $formKey }}-vagas_externa">Vagas Comunidade Externa</label>
        <input type="number" class="form-control{{ $invalido('vagas_externa') }}" id="{{ $formKey }}-vagas_externa" name="vagas_externa" min="0" step="1" value="{{ $valor('vagas_externa') }}">
    </div>
</div>

<div class="form-group">
    <label for="{{ $formKey }}-observacoes">Observações</label>
    <textarea class="form-control{{ $invalido('observacoes') }}" id="{{ $formKey }}-observacoes" name="observacoes" rows="3">{{ $valor('observacoes') }}</textarea>
</div>

<h6 class="text-secondary font-weight-bold mt-4">Taxas do Primeiro Período de Inscrição</h6>
<div class="form-row">
    <div class="form-group col-md">
        <label for="{{ $formKey }}-taxa_usp">Comunidade USP</label>
        <input type="number" class="form-control{{ $invalido('taxa_usp') }}" id="{{ $formKey }}-taxa_usp" name="taxa_usp" min="0" step="0.01" value="{{ $valor('taxa_usp') }}">
    </div>
    <div class="form-group col-md">
        <label for="{{ $formKey }}-taxa_dependentes">Dependentes</label>
        <input type="number" class="form-control{{ $invalido('taxa_dependentes') }}" id="{{ $formKey }}-taxa_dependentes" name="taxa_dependentes" min="0" step="0.01" value="{{ $valor('taxa_dependentes') }}">
    </div>
    <div class="form-group col-md">
        <label for="{{ $formKey }}-taxa_externa">Comunidade Externa</label>
        <input type="number" class="form-control{{ $invalido('taxa_externa') }}" id="{{ $formKey }}-taxa_externa" name="taxa_externa" min="0" step="0.01" value="{{ $valor('taxa_externa') }}">
    </div>
    <div class="form-group col-md">
        <label for="{{ $formKey }}-taxa_terceira_idade">Terceira Idade</label>
        <input type="number" class="form-control{{ $invalido('taxa_terceira_idade') }}" id="{{ $formKey }}-taxa_terceira_idade" name="taxa_terceira_idade" min="0" step="0.01" value="{{ $valor('taxa_terceira_idade') }}">
    </div>
    <div class="form-group col-md">
        <label for="{{ $formKey }}-taxa_cepe">Comunidade CEPEUSP</label>
        <input type="number" class="form-control{{ $invalido('taxa_cepe') }}" id="{{ $formKey }}-taxa_cepe" name="taxa_cepe" min="0" step="0.01" value="{{ $valor('taxa_cepe') }}">
    </div>
</div>

<h6 class="text-secondary font-weight-bold mt-3">Taxas do Segundo Período de Inscrição</h6>
<div class="form-row">
    <div class="form-group col-md">
        <label for="{{ $formKey }}-taxa_usp_2">Comunidade USP</label>
        <input type="number" class="form-control{{ $invalido('taxa_usp_2') }}" id="{{ $formKey }}-taxa_usp_2" name="taxa_usp_2" min="0" step="0.01" value="{{ $valor('taxa_usp_2') }}">
    </div>
    <div class="form-group col-md">
        <label for="{{ $formKey }}-taxa_dependentes_2">Dependentes</label>
        <input type="number" class="form-control{{ $invalido('taxa_dependentes_2') }}" id="{{ $formKey }}-taxa_dependentes_2" name="taxa_dependentes_2" min="0" step="0.01" value="{{ $valor('taxa_dependentes_2') }}">
    </div>
    <div class="form-group col-md">
        <label for="{{ $formKey }}-taxa_externa_2">Comunidade Externa</label>
        <input type="number" class="form-control{{ $invalido('taxa_externa_2') }}" id="{{ $formKey }}-taxa_externa_2" name="taxa_externa_2" min="0" step="0.01" value="{{ $valor('taxa_externa_2') }}">
    </div>
    <div class="form-group col-md">
        <label for="{{ $formKey }}-taxa_terceira_idade_2">Terceira Idade</label>
        <input type="number" class="form-control{{ $invalido('taxa_terceira_idade_2') }}" id="{{ $formKey }}-taxa_terceira_idade_2" name="taxa_terceira_idade_2" min="0" step="0.01" value="{{ $valor('taxa_terceira_idade_2') }}">
    </div>
    <div class="form-group col-md">
        <label for="{{ $formKey }}-taxa_cepe_2">Comunidade CEPEUSP</label>
        <input type="number" class="form-control{{ $invalido('taxa_cepe_2') }}" id="{{ $formKey }}-taxa_cepe_2" name="taxa_cepe_2" min="0" step="0.01" value="{{ $valor('taxa_cepe_2') }}">
    </div>
</div>

<div class="form-group">
    <label for="{{ $formKey }}-info_contato">Informações para Contato</label>
    <textarea class="form-control{{ $invalido('info_contato') }}" id="{{ $formKey }}-info_contato" name="info_contato" rows="3">{{ $valor('info_contato') }}</textarea>
</div>

<div class="form-group">
    <label for="{{ $formKey }}-declaracao">Declaração do Usuário</label>
    <textarea class="form-control{{ $invalido('declaracao') }}" id="{{ $formKey }}-declaracao" name="declaracao" rows="3">{{ $valor('declaracao') }}</textarea>
</div>
