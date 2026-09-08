
@extends('layout')

@section('content')

<form action="/oferecimentos/{{ $atividade->id }}" method="POST" class="p-4 bg-white rounded border">
    @csrf
    <input type="hidden" name="atividade_id" value="{{ $atividade->id }}">

    <h5 class="mb-3">{{ $atividade->nome }}</h5>

    <!-- Opções Superiores -->
    <div class="form-check mb-2">
        <input type="checkbox" class="form-check-input" id="gratuito_ou_sem_pagamento" name="gratuito_ou_sem_pagamento" value="1" {{ old('gratuito_ou_sem_pagamento') ? 'checked' : '' }}>
        <label class="form-check-label" for="gratuito_ou_sem_pagamento">Gratuito ou sem Pagamento On-Line:</label>
    </div>

    <div class="form-check mb-3">
        <input type="checkbox" class="form-check-input" id="sem_inscricoes" name="sem_inscricoes" value="1" {{ old('sem_inscricoes') ? 'checked' : '' }}>
        <label class="form-check-label" for="sem_inscricoes">Sem inscrições:</label>
    </div>

    <!-- Período -->
    <div class="d-flex align-items-center gap-2 mb-3">
        <label class="form-label mb-0">Período:</label>
        <select name="periodo_semestre" class="form-select form-select-sm" style="width: auto;">
            <option value="01" {{ old('periodo_semestre') == '01' ? 'selected' : '' }}>01</option>
            <option value="02" {{ old('periodo_semestre', '02') == '02' ? 'selected' : '' }}>02</option>
        </select>
        <select name="periodo_ano" class="form-select form-select-sm" style="width: auto;">
            @for($ano = date('Y'); $ano <= date('Y') + 5; $ano++)
                <option value="{{ $ano }}" {{ old('periodo_ano', '2026') == $ano ? 'selected' : '' }}>{{ $ano }}</option>
            @endfor
        </select>
    </div>

    <!-- Curso Regular -->
    <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
        <div class="form-check me-3">
            <input type="checkbox" class="form-check-input" id="curso_regular" name="curso_regular" value="1" {{ old('curso_regular') ? 'checked' : '' }}>
            <label class="form-check-label" for="curso_regular">Curso Regular:</label>
        </div>
        <span>Aplicar datas a todos os cursos regulares:</span>
        <button type="button" class="btn btn-light btn-sm border">Aplicar Datas a Todos os Cursos</button>
    </div>

    @php
        $periodos = [
            'usp'      => 'Período de Inscrições Comunidade USP:',
            'papfe'    => 'Período de Inscrições PAPFE:',
            'externa'  => 'Período de Inscrições Comunidade Externa:',
            'segundo'  => 'Segundo Período de Inscrições:',
            'curso'    => 'Período do Curso:',
        ];
    @endphp

    <!-- Seções de Datas e Horários -->
    @foreach($periodos as $key => $titulo)
        <div class="mb-4">
            <h6 class="fw-normal text-secondary mb-2">{{ $titulo }}</h6>
            <div class="d-flex align-items-center flex-wrap gap-3 ms-2">
                
                <!-- Início -->
                <div class="d-flex align-items-center gap-1">
                    <span class="me-1">Início:</span>
                    <small>Data:</small>
                    <select name="{{ $key }}_inicio_dia" class="form-select form-select-sm" style="width: 65px;">
                        @for($d = 1; $d <= 31; $d++)
                            @php $dF = sprintf('%02d', $d); @endphp
                            <option value="{{ $dF }}" {{ old("{$key}_inicio_dia") == $dF ? 'selected' : '' }}>{{ $dF }}</option>
                        @endfor
                    </select>
                    /
                    <select name="{{ $key }}_inicio_mes" class="form-select form-select-sm" style="width: 65px;">
                        @for($m = 1; $m <= 12; $m++)
                            @php $mF = sprintf('%02d', $m); @endphp
                            <option value="{{ $mF }}" {{ old("{$key}_inicio_mes") == $mF ? 'selected' : '' }}>{{ $mF }}</option>
                        @endfor
                    </select>
                    /
                    <select name="{{ $key }}_inicio_ano" class="form-select form-select-sm" style="width: 80px;">
                        @for($y = date('Y'); $y <= date('Y') + 5; $y++)
                            <option value="{{ $y }}" {{ old("{$key}_inicio_ano", '2026') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>

                    <small class="ms-2">Horário:</small>
                    <select name="{{ $key }}_inicio_hora" class="form-select form-select-sm" style="width: 60px;">
                        @for($h = 0; $h < 24; $h++)
                            @php $hF = sprintf('%02d', $h); @endphp
                            <option value="{{ $hF }}" {{ old("{$key}_inicio_hora") == $hF ? 'selected' : '' }}>{{ $hF }}</option>
                        @endfor
                    </select>
                    :
                    <select name="{{ $key }}_inicio_minuto" class="form-select form-select-sm" style="width: 60px;">
                        @for($min = 0; $min < 60; $min += 5)
                            @php $minF = sprintf('%02d', $min); @endphp
                            <option value="{{ $minF }}" {{ old("{$key}_inicio_minuto") == $minF ? 'selected' : '' }}>{{ $minF }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Fim -->
                <div class="d-flex align-items-center gap-1 ms-lg-4">
                    <span class="me-1">Fim:</span>
                    <small>Data:</small>
                    <select name="{{ $key }}_fim_dia" class="form-select form-select-sm" style="width: 65px;">
                        @for($d = 1; $d <= 31; $d++)
                            @php $dF = sprintf('%02d', $d); @endphp
                            <option value="{{ $dF }}" {{ old("{$key}_fim_dia") == $dF ? 'selected' : '' }}>{{ $dF }}</option>
                        @endfor
                    </select>
                    /
                    <select name="{{ $key }}_fim_mes" class="form-select form-select-sm" style="width: 65px;">
                        @for($m = 1; $m <= 12; $m++)
                            @php $mF = sprintf('%02d', $m); @endphp
                            <option value="{{ $mF }}" {{ old("{$key}_fim_mes") == $mF ? 'selected' : '' }}>{{ $mF }}</option>
                        @endfor
                    </select>
                    /
                    <select name="{{ $key }}_fim_ano" class="form-select form-select-sm" style="width: 80px;">
                        @for($y = date('Y'); $y <= date('Y') + 5; $y++)
                            <option value="{{ $y }}" {{ old("{$key}_fim_ano", '2026') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>

                    <small class="ms-2">Horário:</small>
                    <select name="{{ $key }}_fim_hora" class="form-select form-select-sm" style="width: 60px;">
                        @for($h = 0; $h < 24; $h++)
                            @php $hF = sprintf('%02d', $h); @endphp
                            <option value="{{ $hF }}" {{ old("{$key}_fim_hora") == $hF ? 'selected' : '' }}>{{ $hF }}</option>
                        @endfor
                    </select>
                    :
                    <select name="{{ $key }}_fim_minuto" class="form-select form-select-sm" style="width: 60px;">
                        @for($min = 0; $min < 60; $min += 5)
                            @php $minF = sprintf('%02d', $min); @endphp
                            <option value="{{ $minF }}" {{ old("{$key}_fim_minuto") == $minF ? 'selected' : '' }}>{{ $minF }}</option>
                        @endfor
                    </select>
                </div>

            </div>
        </div>
    @endforeach

    <!-- Rodapé: Turmas e Atestados -->
    <div class="d-flex align-items-center gap-4 mt-4 pt-2 flex-wrap">
        <div class="d-flex align-items-center gap-2">
            <label for="turmas" class="form-label mb-0">Turmas:</label>
            <input type="number" id="turmas" name="turmas" class="form-control form-control-sm" value="{{ old('turmas', 3) }}" style="width: 60px;">
        </div>

        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="atestado_medico" name="atestado_medico" value="1" {{ old('atestado_medico', true) ? 'checked' : '' }}>
            <label class="form-check-label" for="atestado_medico">Atestado Médico Obrigatório:</label>
        </div>

        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="exame_dermatologico" name="exame_dermatologico" value="1" {{ old('exame_dermatologico') ? 'checked' : '' }}>
            <label class="form-check-label" for="exame_dermatologico">Exame Dermatológico Obrigatório:</label>
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary">Enviar</button>
        <a href="/atividades/{{ $atividade->id }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
@endsection