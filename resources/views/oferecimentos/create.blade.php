@extends('layout')

@section('content')

<form action="/oferecimentos/{{ $atividade->id }}" method="POST" class="p-4 bg-white rounded border shadow-sm">
    @csrf
    <input type="hidden" name="atividade_id" value="{{ $atividade->id }}">

    <h4 class="mb-4 text-primary">{{ $atividade->nome }}</h4>

    <!-- Forma de Pagamento -->
    <div class="card p-3 mb-4 bg-light border-0">
        <label class="form-label fw-bold text-secondary mb-2">Forma de Pagamento / Isenção:</label>
        <div class="d-flex align-items-center gap-4 flex-wrap">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="pagamento" id="pagamento_pix" value="pix" {{ old('pagamento', 'pix') == 'pix' ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="pagamento_pix">
                    Pix
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="pagamento" id="pagamento_boleto" value="boleto" {{ old('pagamento') == 'boleto' ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="pagamento_boleto">
                    Boleto
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="pagamento" id="pagamento_ambos" value="ambos" {{ old('pagamento') == 'ambos' ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="pagamento_ambos">
                    Ambos (Pix e Boleto)
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="pagamento" id="pagamento_gratuito" value="gratuito" {{ old('pagamento') == 'gratuito' ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold text-success" for="pagamento_gratuito">
                    Gratuito
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="pagamento" id="pagamento_sem_inscricoes" value="sem_inscricoes" {{ old('pagamento') == 'sem_inscricoes' ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold text-secondary" for="pagamento_sem_inscricoes">
                    Sem inscrições
                </label>
            </div>
        </div>
    </div>

    <!-- Período de Oferta -->
    <div class="row g-3 align-items-center mb-4">
        <div class="col-auto">
            <label class="col-form-label fw-bold">Período de Oferta:</label>
        </div>
        <div class="col-auto">
            <select name="periodo_semestre" class="form-select form-select-sm">
                <option value="01" {{ old('periodo_semestre') == '01' ? 'selected' : '' }}>01</option>
                <option value="02" {{ old('periodo_semestre', '02') == '02' ? 'selected' : '' }}>02</option>
            </select>
        </div>
        <div class="col-auto">
            <select name="periodo_ano" class="form-select form-select-sm">
                @for($ano = date('Y'); $ano <= date('Y') + 5; $ano++)
                    <option value="{{ $ano }}" {{ old('periodo_ano', date('Y')) == $ano ? 'selected' : '' }}>{{ $ano }}</option>
                @endfor
            </select>
        </div>
    </div>

    @php
        $perfis = config('cepe.perfil', []);
    @endphp

    <!-- Seções de Datas por Perfil -->
    <h5 class="mt-4 mb-3 text-secondary border-bottom pb-2">Períodos de Inscrição por Perfil</h5>

    @foreach($perfis as $key => $titulo)
        @php
            $isAtivo = old("periodos.{$key}.ativo") == '1';
        @endphp
        <div class="card mb-3 p-3 {{ $isAtivo ? 'border-primary' : '' }}" id="card_{{ $key }}">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input perfil-switch" 
                           type="checkbox" 
                           role="switch"
                           name="periodos[{{ $key }}][ativo]" 
                           value="1" 
                           id="ativo_{{ $key }}" 
                           data-key="{{ $key }}"
                           {{ $isAtivo ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold fs-6 ms-2" for="ativo_{{ $key }}">
                        {{ $titulo }} <span class="badge bg-secondary text-uppercase ms-1">{{ $key }}</span>
                    </label>
                </div>
            </div>

            <!-- Inputs de Data/Hora -->
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="inicio_{{ $key }}" class="form-label small fw-semibold text-muted mb-1">Início da Inscrição</label>
                          <input type="text" 
                              class="form-control form-control-sm datepicker hasDatepicker campo-data-{{ $key }}" 
                              id="inicio_{{ $key }}" 
                              name="periodos[{{ $key }}][inicio]" 
                              value="{{ old("periodos.{$key}.inicio") }}"
                              {{ !$isAtivo ? 'disabled' : '' }}>
                </div>

                <div class="col-md-6">
                    <label for="fim_{{ $key }}" class="form-label small fw-semibold text-muted mb-1">Término da Inscrição</label>
                          <input type="text" 
                              class="form-control form-control-sm datepicker hasDatepicker campo-data-{{ $key }}" 
                              id="fim_{{ $key }}" 
                              name="periodos[{{ $key }}][fim]" 
                              value="{{ old("periodos.{$key}.fim") }}"
                              {{ !$isAtivo ? 'disabled' : '' }}>
                </div>
            </div>
        </div>
    @endforeach

    <!-- Requisitos -->
    <div class="card p-3 my-4 bg-light border-0">
        <div class="d-flex align-items-center gap-4 flex-wrap">
            <div class="form-check form-switch mb-0">
                <input type="checkbox" class="form-check-input" id="atestado_medico" name="atestado_medico" value="1" {{ old('atestado_medico', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="atestado_medico">Atestado Médico Obrigatório</label>
            </div>
            <div class="form-check form-switch mb-0">
                <input type="checkbox" class="form-check-input" id="exame_dermatologico" name="exame_dermatologico" value="1" {{ old('exame_dermatologico') ? 'checked' : '' }}>
                <label class="form-check-label" for="exame_dermatologico">Exame Dermatológico Obrigatório</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">Salvar</button>
        <a href="/atividades/{{ $atividade->id }}" class="btn btn-outline-secondary px-4">Cancelar</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Controle dos Switches de Perfil
    const switches = document.querySelectorAll('.perfil-switch');

    switches.forEach(switchEl => {
        switchEl.addEventListener('change', function () {
            const key = this.dataset.key;
            const campos = document.querySelectorAll(`.campo-data-${key}`);
            const card = document.getElementById(`card_${key}`);

            campos.forEach(campo => {
                campo.disabled = !this.checked;
                if (!this.checked) {
                    campo.value = '';
                }
            });

            if (this.checked) {
                card.classList.add('border-primary');
            } else {
                card.classList.remove('border-primary');
            }
        });
    });

    // 2. Desativa opções de pagamento se "Gratuito" estiver marcado
    const gratuitoCheckbox = document.getElementById('gratuito_ou_sem_pagamento');
    const meiosPagamento = document.querySelectorAll('.meio-pagamento');
    const cardPagamento = document.getElementById('card_pagamento');

    function togglePagamento() {
        const isGratuito = gratuitoCheckbox.checked;
        meiosPagamento.forEach(radio => {
            radio.disabled = isGratuito;
        });
        if (isGratuito) {
            cardPagamento.classList.add('opacity-50');
        } else {
            cardPagamento.classList.remove('opacity-50');
        }
    }

    gratuitoCheckbox.addEventListener('change', togglePagamento);
    togglePagamento(); // Executa ao carregar a página
});
</script>
@endsection