@extends('layout')

@section('content')
<div class="container py-4">

    @include('oferecimentos.partials.periodos')

    @php
        $formasPagamento = (array) ($oferecimento->formas_pagamento ?? []);
        if ($oferecimento->gratuito_ou_sem_pagamento) {
            array_unshift($formasPagamento, 'Gratuito ou sem Pagamento On-Line');
        }
        $rotulosPagamento = ['pix' => 'Pix', 'boleto' => 'Boleto'];

        $fmt = function ($base) use ($oferecimento) {
            $partes = [];
            $data  = $oferecimento->{"{$base}_data"};
            $hora  = $oferecimento->{"{$base}_hora"};
            if ($data) {
                $partes[] = $data;
            }
            if ($hora !== null && $hora !== '') {
                $partes[] = sprintf('%02d:%02d', (int) $hora, (int) ($oferecimento->{"{$base}_minuto"} ?? 0));
            }
            return implode(' ', $partes) ?: '-';
        };
    @endphp

    <!-- Card de Detalhes do Oferecimento -->
    <div class="card shadow-sm col-md-10 mx-auto mb-4 p-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h1 class="h4 mb-0 text-primary">Detalhes do Oferecimento</h1>
            <a href="/atividades/{{ $atividade->id }}" class="btn btn-outline-secondary btn-sm">
                &larr; Voltar
            </a>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="text-muted font-weight-bold mb-1">Atividade</label>
                    <p class="h5 mb-0">
                        <a href="/atividades/{{ $atividade->id }}">{{ $atividade->nome }}</a>
                    </p>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="text-muted font-weight-bold mb-1">Oferecimento</label>
                    <p class="h5 mb-0">
                        #{{ $oferecimento->id }}
                        @if($oferecimento->periodo_semestre || $oferecimento->periodo_ano)
                            <span class="text-muted h6">({{ $oferecimento->periodo_semestre }}/{{ $oferecimento->periodo_ano }})</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="text-muted font-weight-bold mb-1">Pagamento</label>
                    <p class="mb-0">
                        @forelse($formasPagamento as $forma)
                            <span class="badge badge-secondary">{{ $rotulosPagamento[$forma] ?? $forma }}</span>
                        @empty
                            -
                        @endforelse
                    </p>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="text-muted font-weight-bold mb-1">Atestado Médico Obrigatório</label>
                    <p class="mb-0">{{ $oferecimento->atestado_medico ? 'Sim' : 'Não' }}</p>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="text-muted font-weight-bold mb-1">Exame Dermatológico Obrigatório</label>
                    <p class="mb-0">{{ $oferecimento->exame_dermatologico ? 'Sim' : 'Não' }}</p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="text-muted font-weight-bold mb-1">Cadastrado por</label>
                    <p class="mb-0">{{ $oferecimento->user?->name ?? '-' }}</p>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="text-muted font-weight-bold mb-1">Criado em</label>
                    <p class="mb-0">{{ $oferecimento->created_at?->format('d/m/Y H:i') ?? '-' }}</p>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="text-muted font-weight-bold mb-1">Atualizado em</label>
                    <p class="mb-0">{{ $oferecimento->updated_at?->format('d/m/Y H:i') ?? '-' }}</p>
                </div>
            </div>
        </div>

        <div class="card-footer bg-white text-right py-3">
            <a href="/oferecimentos/{{ $atividade->id }}/{{ $oferecimento->id }}/edit" class="btn btn-warning text-white mr-2">
                Editar
            </a>

            <button type="submit" form="apagar-oferecimento" class="btn btn-danger"
                onclick="return confirm('Tem certeza que deseja apagar este oferecimento?');">
                Apagar
            </button>
        </div>
    </div>

    <form id="apagar-oferecimento" action="/oferecimentos/{{ $atividade->id }}/{{ $oferecimento->id }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>

    <!-- Períodos -->
    <div class="card shadow-sm col-md-10 mx-auto mb-4 p-0">
        <div class="card-header bg-white py-3">
            <h2 class="h5 mb-0">Períodos</h2>
        </div>

        <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0">
                <thead>
                    <tr>
                        <th>Período</th>
                        <th>Início</th>
                        <th>Fim</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($periodos as $key => $titulo)
                        <tr>
                            <td>{{ $titulo }}</td>
                            <td>{{ $fmt("{$key}_inicio") }}</td>
                            <td>{{ $fmt("{$key}_fim") }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Turmas do Oferecimento -->
    <div class="card shadow-sm col-md-10 mx-auto p-0">
        <div class="card-header bg-white py-3">
            <h2 class="h5 mb-0">Turmas</h2>
        </div>

        <div class="card-body">
            <div id="accordionTurmas">
                @foreach($oferecimento->turmas as $turma)
                    @php
                        $abrir = old('form_key') === 'turma-' . $turma->id;
                        $resumo = collect([
                            $turma->professor,
                            collect((array) $turma->dias_semana)->map(fn($d) => ucfirst($d))->implode(', ') ?: null,
                            $turma->horario_inicio ? substr($turma->horario_inicio, 0, 5) . ' - ' . substr((string) $turma->horario_fim, 0, 5) : null,
                        ])->filter()->implode(' | ');
                    @endphp
                    <div class="card mb-2">
                        <div class="card-header bg-light p-0" id="cabecalho-turma-{{ $turma->id }}">
                            <button class="btn btn-link btn-block text-left text-dark p-3 {{ $abrir ? '' : 'collapsed' }}"
                                type="button" data-toggle="collapse" data-target="#corpo-turma-{{ $turma->id }}"
                                aria-expanded="{{ $abrir ? 'true' : 'false' }}" aria-controls="corpo-turma-{{ $turma->id }}">
                                <span class="font-weight-bold">Turma {{ $loop->iteration }}</span>
                                <span class="text-muted small ml-2">{{ $resumo }}</span>
                            </button>
                        </div>

                        <div id="corpo-turma-{{ $turma->id }}" class="collapse {{ $abrir ? 'show' : '' }}"
                            aria-labelledby="cabecalho-turma-{{ $turma->id }}" data-parent="#accordionTurmas">
                            <div class="card-body">
                                <form method="POST" action="/turmas/{{ $turma->id }}">
                                    @csrf
                                    @method('PATCH')

                                    @include('turmas.partials.form', ['turma' => $turma, 'oferecimento' => $oferecimento])

                                    <div class="text-right">
                                        <button type="submit" class="btn btn-primary mr-2">Salvar Turma</button>
                                        <button type="submit" form="apagar-turma-{{ $turma->id }}" class="btn btn-outline-danger"
                                            onclick="return confirm('Tem certeza que deseja apagar esta turma?');">
                                            Apagar Turma
                                        </button>
                                    </div>
                                </form>

                                <form id="apagar-turma-{{ $turma->id }}" action="/turmas/{{ $turma->id }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- Nova Turma -->
                @php $abrirNova = old('form_key') === 'turma-nova'; @endphp
                <div class="card">
                    <div class="card-header bg-light p-0" id="cabecalho-turma-nova">
                        <button class="btn btn-link btn-block text-left p-3 {{ $abrirNova ? '' : 'collapsed' }}"
                            type="button" data-toggle="collapse" data-target="#corpo-turma-nova"
                            aria-expanded="{{ $abrirNova ? 'true' : 'false' }}" aria-controls="corpo-turma-nova">
                            <span class="font-weight-bold text-success">+ Nova Turma</span>
                        </button>
                    </div>

                    <div id="corpo-turma-nova" class="collapse {{ $abrirNova ? 'show' : '' }}"
                        aria-labelledby="cabecalho-turma-nova" data-parent="#accordionTurmas">
                        <div class="card-body">
                            <form method="POST" action="/turmas">
                                @csrf

                                @include('turmas.partials.form', ['turma' => null, 'oferecimento' => $oferecimento])

                                <div class="text-right">
                                    <button type="submit" class="btn btn-success">Criar Turma</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
