<?php

namespace App\Http\Controllers\Carteiras;

use App\Http\Controllers\Controller;
use App\Models\CarteiraTaxa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TaxaCarteiraController extends Controller
{
    public function edit()
    {
        Gate::authorize('taxas.gerenciar');

        return view('carteiras.taxas', [
            'categorias' => config('cepe.carteiras.categorias'),
            'taxas' => CarteiraTaxa::with('atualizador')->get()->keyBy('categoria'),
        ]);
    }

    public function update(Request $request)
    {
        Gate::authorize('taxas.gerenciar');
        $categorias = array_keys(config('cepe.carteiras.categorias'));

        $dados = $request->validate([
            'taxas' => 'required|array:'.implode(',', $categorias),
            'taxas.*' => 'array:valor,valor_dependente,observacao',
            'taxas.*.valor' => 'nullable|integer|min:0|max:999999',
            'taxas.*.valor_dependente' => 'nullable|integer|min:0|max:999999',
            'taxas.*.observacao' => 'nullable|string|max:255',
        ], [], ['taxas.*.valor' => 'taxa', 'taxas.*.valor_dependente' => 'taxa de dependente', 'taxas.*.observacao' => 'observação']);

        DB::transaction(function () use ($dados, $request) {
            foreach ($dados['taxas'] as $categoria => $taxa) {
                $valores = [
                    'valor' => $taxa['valor'] ?? null,
                    'valor_dependente' => $taxa['valor_dependente'] ?? null,
                    'observacao' => $taxa['observacao'] ?? null,
                ];
                $atual = CarteiraTaxa::firstOrNew(['categoria' => $categoria]);
                if (! $atual->exists && array_filter($valores, fn ($v) => $v !== null) === []) {
                    continue;
                }
                $atual->fill($valores);
                if ($atual->isDirty()) {
                    $atual->atualizado_por = $request->user()->id;
                    $atual->save();
                }
            }
        });

        return redirect('/carteiras-taxas')->with('alert-success', 'Taxas atualizadas.');
    }
}
