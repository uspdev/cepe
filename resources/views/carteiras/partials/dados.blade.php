<p><strong>Categoria:</strong> {{ $solicitacao->categoria_nome }}</p>
<p><strong>Beneficiário:</strong> {{ $solicitacao->nome }}@if($solicitacao->parentesco_nome) — {{ $solicitacao->parentesco_nome }}@endif</p>
<p>
    <strong>CPF:</strong> {{ $solicitacao->cpf ?? '-' }}
    &nbsp; <strong>Nascimento:</strong> {{ $solicitacao->data_nascimento?->format('d/m/Y') ?? '-' }}
</p>
<p>
    <strong>Status da solicitação:</strong> <span class="badge badge-secondary">{{ $solicitacao->status_nome }}</span>
    @if($solicitacao->analisador) <small class="text-muted">por {{ $solicitacao->analisador->name }} em {{ $solicitacao->analisado_em?->format('d/m/Y H:i') }}</small>@endif
</p>
@if($solicitacao->carteira)
    <p>
        <strong>Carteira:</strong> {{ $solicitacao->carteira->numero }}
        &nbsp; <strong>Status:</strong> {{ $solicitacao->carteira->status_nome }}
        @if($solicitacao->carteira->via) &nbsp; <strong>Via:</strong> {{ $solicitacao->carteira->via }}ª @endif
        &nbsp; <strong>Validade:</strong> {{ $solicitacao->carteira->validade_inicio?->format('d/m/Y') }} a {{ $solicitacao->carteira->validade_fim?->format('d/m/Y') }}
    </p>
@endif
@if($solicitacao->observacao)<p><strong>Observação da SRU:</strong> {{ $solicitacao->observacao }}</p>@endif

<h6 class="mt-3">Documentos enviados</h6>
<ul>
    @forelse($solicitacao->documentos as $documento)
        <li><a href="/carteiras/{{ $solicitacao->id }}/documentos/{{ $documento->id }}">{{ $documento->tipo_nome }}</a> <small class="text-muted">{{ $documento->updated_at?->format('d/m/Y H:i') }}</small></li>
    @empty
        <li class="text-muted">Nenhum documento.</li>
    @endforelse
</ul>
