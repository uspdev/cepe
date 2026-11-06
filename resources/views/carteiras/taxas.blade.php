@extends('layout')

@section('content')
@php($erro = fn ($campo) => $errors->has($campo) ? ' is-invalid' : '')
<div class="container py-4">
    <div class="card shadow-sm col-md-10 mx-auto p-0">
        <div class="card-header bg-white py-3">
            <h1 class="h4 mb-0 text-primary">Taxas das carteiras CEPEUSP</h1>
        </div>

        <form method="POST" action="/carteiras-taxas">
            @csrf
            @method('PUT')
            <div class="card-body p-0">
                @if($errors->any())
                    <div class="alert alert-danger m-3"><ul class="mb-0 pl-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                @endif
                <p class="text-muted small px-3 pt-3">
                    Valores anuais em reais. Categorias sem taxa preenchida exibem o texto padrão indicado abaixo do nome.
                </p>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="thead-light">
                            <tr><th>Categoria</th><th style="width: 150px">Taxa (R$)</th><th style="width: 150px">Dependente (R$)</th><th>Observação</th></tr>
                        </thead>
                        <tbody>
                            @foreach($categorias as $chave => $categoria)
                                @php($taxa = $taxas->get($chave))
                                <tr>
                                    <td>
                                        {{ $categoria['rotulo'] }}
                                        @if(filled($categoria['taxa'] ?? null))<br><small class="text-muted">Padrão: {{ $categoria['taxa'] }}</small>@endif
                                        @if($taxa?->atualizador)<br><small class="text-muted">Atualizado por {{ $taxa->atualizador->name }} em {{ $taxa->updated_at?->format('d/m/Y H:i') }}</small>@endif
                                    </td>
                                    <td>
                                        <input type="number" min="0" step="0.01" class="form-control form-control-sm{{ $erro("taxas.{$chave}.valor") }}" aria-label="Taxa {{ $categoria['rotulo'] }}"
                                            name="taxas[{{ $chave }}][valor]" value="{{ old("taxas.{$chave}.valor", $taxa?->valor) }}">
                                    </td>
                                    <td>
                                        <input type="number" min="0" step="0.01" class="form-control form-control-sm{{ $erro("taxas.{$chave}.valor_dependente") }}" aria-label="Taxa de dependente {{ $categoria['rotulo'] }}"
                                            name="taxas[{{ $chave }}][valor_dependente]" value="{{ old("taxas.{$chave}.valor_dependente", $taxa?->valor_dependente) }}">
                                    </td>
                                    <td>
                                        <input type="text" maxlength="255" class="form-control form-control-sm{{ $erro("taxas.{$chave}.observacao") }}" aria-label="Observação {{ $categoria['rotulo'] }}"
                                            name="taxas[{{ $chave }}][observacao]" value="{{ old("taxas.{$chave}.observacao", $taxa?->observacao) }}" placeholder="ex.: em 3 vezes no boleto ou Pix">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white text-right py-3">
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>
@endsection
