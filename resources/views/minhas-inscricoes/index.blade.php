@extends('layout')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h1 class="h4 mb-0 text-primary">Minhas inscrições</h1>
            <div class="d-flex gap-1 justify-content-between">
                <a href="{{ route('catalogo.cursos') }}" class="btn btn-outline-primary btn-sm m-2">Ver cursos</a>
                <a href="{{ route('catalogo.eventos') }}" class="btn btn-outline-primary btn-sm m-2">Ver eventos</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Atividade</th>
                            <th>Turma</th>
                            <th>Período</th>
                            <th>Perfil</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th class="text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($matriculas as $matricula)
                            @php
                                $turma = $matricula->turma;
                                $oferecimento = $turma?->oferecimento;
                            @endphp
                            <tr>
                                <td>{{ $oferecimento?->atividade?->nome ?? '—' }}</td>
                                <td class="small">
                                    @if($turma)
                                        {{ $turma->dias_semana_formatados }} {{ $turma->horario_formatado }}
                                        @if($turma->professor)<br><span class="text-muted">{{ $turma->professor->name }}</span>@endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $matricula->periodo_oferecimento }}</td>
                                <td>{{ $matricula->perfil_nome }}</td>
                                <td class="small">
                                    @if($matricula->valor !== null)
                                        R$ {{ number_format($matricula->valor, 2, ',', '.') }}
                                        @if($matricula->credito_usado != 0)
                                            <br><span class="text-muted">crédito {{ $matricula->credito_usado > 0 ? 'usado' : 'gerado' }}: R$ {{ number_format(abs($matricula->credito_usado), 2, ',', '.') }}</span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $matricula->status === 'matriculada' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $matricula->status_nome }}
                                    </span>
                                    @foreach($matricula->cobrancas as $cobranca)
                                        <div class="small mt-1">
                                            Parcela {{ $cobranca->parcela }}/{{ $cobranca->total_parcelas }}:
                                            R$ {{ number_format($cobranca->valorReais(), 2, ',', '.') }}
                                            · vence em {{ $cobranca->vencimento->format('d/m/Y') }}
                                            · {{ $cobranca->status_nome }}
                                            @if($cobranca->metodo === \App\Models\Cobranca::PIX)
                                                @if($cobranca->expira_em)
                                                    · expira em {{ $cobranca->expira_em->timezone(config('cepe.fuso_frequencia'))->format('d/m/Y H:i') }}
                                                @endif
                                                @if($cobranca->status === \App\Models\Cobranca::EMITIDA && $cobranca->pix_qr_code_img_base64)
                                                    <div class="mt-2">
                                                        <img src="data:image/png;base64,{{ $cobranca->pix_qr_code_img_base64 }}" alt="QR Code Pix" width="160" height="160">
                                                    </div>
                                                @endif
                                                @if($cobranca->status === \App\Models\Cobranca::EMITIDA && $cobranca->pix_copia_cola)
                                                    <div class="mt-2">
                                                        <label for="pix-{{ $cobranca->id }}">Código Pix copia e cola</label>
                                                        <textarea id="pix-{{ $cobranca->id }}" class="form-control" rows="3" readonly>{{ $cobranca->pix_copia_cola }}</textarea>
                                                        <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="navigator.clipboard.writeText(document.getElementById('pix-{{ $cobranca->id }}').value)">Copiar código Pix</button>
                                                    </div>
                                                @endif
                                            @elseif($cobranca->codigo_boleto)
                                                · <a href="{{ route('minhas-inscricoes.boletos.pdf', [$matricula, $cobranca]) }}">Ver boleto</a>
                                            @endif
                                        </div>
                                    @endforeach
                                    @if($matricula->presencas->isNotEmpty())
                                        <div class="small text-muted mt-1">
                                            Presenças: {{ $matricula->presencas->where('status', \App\Models\Presenca::PRESENTE)->count() }}
                                            · Faltas: {{ $matricula->presencas->where('status', \App\Models\Presenca::FALTA)->count() }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @can('checkin', $matricula)
                                        @if($frequencia->janelaAberta($turma))
                                            <form method="POST" action="{{ route('minhas-inscricoes.presenca', $matricula) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success">Marcar presença</button>
                                            </form>
                                        @endif
                                    @endcan
                                    @if(in_array($matricula->status, [\App\Models\Matricula::MATRICULADA, \App\Models\Matricula::AGUARDANDO_PAGAMENTO], true))
                                        <form method="POST" action="{{ route('minhas-inscricoes.cancelar', $matricula) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tem certeza que deseja cancelar esta inscrição?');">Cancelar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Você ainda não possui inscrições.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
