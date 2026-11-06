<p><strong>Categoria:</strong> {{ $carteira->categoria_nome }}</p>
<p><strong>Beneficiário:</strong> {{ $carteira->nome }}@if($carteira->parentesco_nome) — {{ $carteira->parentesco_nome }}@endif</p>
<p>
    <strong>CPF:</strong> {{ $carteira->cpf ?? '-' }}
    &nbsp; <strong>Nascimento:</strong> {{ $carteira->data_nascimento?->format('d/m/Y') ?? '-' }}
</p>
<p>
    <strong>Status:</strong> <span class="badge badge-secondary">{{ $carteira->status_nome }}</span>
    @if($carteira->analisador) <small class="text-muted">por {{ $carteira->analisador->name }} em {{ $carteira->analisado_em?->format('d/m/Y H:i') }}</small>@endif
</p>
@if($carteira->numero)
    <p>
        <strong>Número:</strong> {{ $carteira->numero }}
        @if($carteira->via) &nbsp; <strong>Via:</strong> {{ $carteira->via }}ª @endif
        &nbsp; <strong>Validade:</strong> {{ $carteira->validade_inicio?->format('d/m/Y') }} a {{ $carteira->validade_fim?->format('d/m/Y') }}
    </p>
@endif
@if($carteira->observacao)<p><strong>Observação da SRU:</strong> {{ $carteira->observacao }}</p>@endif

<h6 class="mt-3">Documentos enviados</h6>
<ul>
    @forelse($carteira->documentos as $documento)
        <li><a href="/carteiras/{{ $carteira->id }}/documentos/{{ $documento->id }}">{{ $documento->tipo_nome }}</a> <small class="text-muted">{{ $documento->updated_at?->format('d/m/Y H:i') }}</small></li>
    @empty
        <li class="text-muted">Nenhum documento.</li>
    @endforelse
</ul>
