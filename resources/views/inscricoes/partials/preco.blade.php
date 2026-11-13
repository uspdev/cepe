@if($dados['valor'] !== null)
@php
    $preco = $dados['preco'];
@endphp
    — R$ {{ number_format($dados['valor'], 2, ',', '.') }}
    @if($preco['desconto'] > 0 || $preco['credito_usado'] != 0 || $preco['final'] > $preco['base'])
        <span class="text-muted small">
            (taxa R$ {{ number_format($preco['base'], 2, ',', '.') }}
            @if($preco['desconto_vinculo'] > 0)
                − R$ {{ number_format($preco['desconto_vinculo'], 2, ',', '.') }} desconto {{ config('cepe.taxa_vinculo.vinculos.' . $preco['vinculo'], $preco['vinculo']) }}
            @endif
            @if($preco['desconto_curso'] > 0)
                − R$ {{ number_format($preco['desconto_curso'], 2, ',', '.') }} desconto de segundo curso
            @endif
            @if($preco['credito_usado'] > 0)
                − R$ {{ number_format($preco['credito_usado'], 2, ',', '.') }} crédito
            @elseif($preco['credito_usado'] < 0)
                · valor mínimo R$ {{ number_format($preco['final'], 2, ',', '.') }}, R$ {{ number_format(-$preco['credito_usado'], 2, ',', '.') }} ficam como crédito
            @endif
            )
        </span>
    @endif
@endif
