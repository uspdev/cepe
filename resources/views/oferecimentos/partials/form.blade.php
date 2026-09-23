@php
    $oferecimento = $oferecimento ?? null;

    // old() tem precedência (retorno de validação); na edição cai no valor atual do model
    $valor = fn($campo, $default = null) => old($campo, $oferecimento?->{$campo} ?? $default);
    $marcou = fn($campo) => (bool) $valor($campo);
    $formasPagamento = (array) old('formas_pagamento', $oferecimento?->formas_pagamento ?? []);

    // Períodos salvos indexados por perfil, para repopular o form na edição
    $periodosSalvos = $oferecimento?->periodos->keyBy('perfil') ?? collect();

    $valorPeriodo = function ($key, $lado, $parte) use ($periodosSalvos) {
        $salvo = $periodosSalvos->get($key)?->{$lado}; // Carbon ou null
        $default = match ($parte) {
            'data'    => $salvo?->format('d/m/Y'),
            'horario' => $salvo ? $salvo->format('H:i') : '00:00',
        };
        return old("periodos.{$key}.{$lado}_{$parte}", $default);
    };

    // Checkbox "ativo" de um período: old() tem precedência; na edição, ativo se existe período salvo
    $periodoAtivo = fn($key) => (bool) old("periodos.{$key}.ativo", $periodosSalvos->has($key));
@endphp

<!-- Pagamento -->
<div class="form-group">
    <label class="d-block mb-1">Pagamento:</label>
    <div class="form-check form-check-inline">
        <input type="checkbox" class="form-check-input" id="gratuito" name="formas_pagamento[]" value="gratuito" {{ in_array('gratuito', $formasPagamento) ? 'checked' : '' }}>
        <label class="form-check-label" for="gratuito">Gratuito ou sem Pagamento On-Line</label>
    </div>
    <div class="form-check form-check-inline">
        <input type="checkbox" class="form-check-input" id="pagamento_pix" name="formas_pagamento[]" value="pix" {{ in_array('pix', $formasPagamento) ? 'checked' : '' }}>
        <label class="form-check-label" for="pagamento_pix">Pix</label>
    </div>
    <div class="form-check form-check-inline">
        <input type="checkbox" class="form-check-input" id="pagamento_boleto" name="formas_pagamento[]" value="boleto" {{ in_array('boleto', $formasPagamento) ? 'checked' : '' }}>
        <label class="form-check-label" for="pagamento_boleto">Boleto</label>
    </div>
</div>
<hr>

<!-- Período -->
<div class="form-row">
    <div class="form-group col-auto">
        <label for="periodo_semestre">Período:</label>
        <select name="periodo_semestre" id="periodo_semestre" class="form-control form-control-sm">
            <option value="01" {{ $valor('periodo_semestre') == '01' ? 'selected' : '' }}>01</option>
            <option value="02" {{ $valor('periodo_semestre', '02') == '02' ? 'selected' : '' }}>02</option>
        </select>
    </div>
    <div class="form-group col-auto">
        <label for="periodo_ano">Ano:</label>
        <select name="periodo_ano" id="periodo_ano" class="form-control form-control-sm">
            @for($ano = date('Y'); $ano <= date('Y') + 5; $ano++)
                <option value="{{ $ano }}" {{ $valor('periodo_ano', date('Y')) == $ano ? 'selected' : '' }}>{{ $ano }}</option>
            @endfor
        </select>
    </div>
</div>

@foreach(config('cepe.periodos') as $key => $titulo)
    <div class="form-group border-top pt-3">
         <label class="toggle-switch-label">
            <input type="checkbox" name="periodos[{{ $key }}][ativo]" value="1" {{ $periodoAtivo($key) ? 'checked' : '' }}>
            <span class="toggle-slider"></span>
        </label>
        <h6 class="font-weight-normal text-secondary">{{ $titulo }}:</h6>
        <div class="d-flex align-items-center flex-wrap ml-2">

            <!-- Início -->
            <div class="d-flex align-items-center flex-wrap mb-2">
                <span class="mr-2">Início:</span>
                <input type="text" name="periodos[{{ $key }}][inicio_data]" value="{{ $valorPeriodo($key, 'inicio', 'data') }}"
                    class="form-control form-control-sm datepicker mr-2" style="width: 110px;" placeholder="dd/mm/aaaa">

                <small class="mr-1">Horário:</small>
                <input type="time" name="periodos[{{ $key }}][inicio_horario]" value="{{ $valorPeriodo($key, 'inicio', 'horario') }}"
                    class="form-control form-control-sm mr-1" style="width: auto;">
            </div>

            <!-- Fim -->
            <div class="d-flex align-items-center flex-wrap ml-lg-4 mb-2">
                <span class="mr-2">Fim:</span>
                <input type="text" name="periodos[{{ $key }}][fim_data]" value="{{ $valorPeriodo($key, 'fim', 'data') }}"
                    class="form-control form-control-sm datepicker mr-2" style="width: 110px;" placeholder="dd/mm/aaaa">

                <small class="mr-1">Horário:</small>
                <input type="time" name="periodos[{{ $key }}][fim_horario]" value="{{ $valorPeriodo($key, 'fim', 'horario') }}"
                    class="form-control form-control-sm mr-1" style="width: auto;">
            </div>

        </div>
    </div>
@endforeach

<!-- Rodapé: Atestados -->
<div class="form-group d-flex align-items-center flex-wrap border-top mt-4 pt-3">

    <div class="form-check mr-4 mb-2">
        <input type="checkbox" class="form-check-input" id="atestado_medico" name="atestado_medico" value="1" {{ $marcou('atestado_medico') ? 'checked' : '' }}>
        <label class="form-check-label" for="atestado_medico">Atestado Médico Obrigatório</label>
    </div>

    <div class="form-check mb-2">
        <input type="checkbox" class="form-check-input" id="exame_dermatologico" name="exame_dermatologico" value="1" {{ $marcou('exame_dermatologico') ? 'checked' : '' }}>
        <label class="form-check-label" for="exame_dermatologico">Exame Dermatológico Obrigatório</label>
    </div>
</div>
