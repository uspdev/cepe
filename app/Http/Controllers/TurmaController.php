<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Turma;
use App\Models\Oferecimento;
use App\Models\VagaTurma;
use App\Http\Requests\TurmaRequest;
use Illuminate\Support\Facades\Gate;

class TurmaController extends Controller
{
    public function index(Request $request){
        Gate::authorize('viewAny', Turma::class);
        $turmas = Turma::whereHas('oferecimento.atividade', fn ($q) => $q->whereIn('tipo', $request->user()->tiposPermitidos()))
            ->when($request->has('search'), fn ($q) => $q->where('oferecimento_id', 'like', '%'.$request->search.'%'))
            ->get();

        return view('turmas.index',[
            'turmas' => $turmas
        ]);
    }

    public function create(){
        Gate::authorize('viewAny', Turma::class);
        return view('turmas.create',[
            'oferecimentos' => $this->oferecimentosPermitidos()
        ]);
    }

    public function store(TurmaRequest $request){
        Gate::authorize('create', [Turma::class, Oferecimento::findOrFail($request->oferecimento_id)]);
        $turma = new Turma;
        $dados = $request->validated();
        unset($dados['taxas'], $dados['vagas']);
        $turma->fill($dados);
        $turma->user_id = auth()->id();
        $turma->save();
        $this->syncTaxas($turma, $request->validated('taxas', []));
        $this->syncVagas($turma, $request->validated('vagas', []));
        return $this->voltarParaOferecimento($turma);
    }

    public function show(Turma $turma){
        Gate::authorize('view', $turma);
        return view('turmas.show',[
            'turma' => $turma
        ]);
    }

    public function edit(Turma $turma){
        Gate::authorize('update', $turma);
        return view('turmas.edit',[
            'turma' => $turma,
            'oferecimentos' => $this->oferecimentosPermitidos()
        ]);
    }

    public function update(TurmaRequest $request, Turma $turma){
        Gate::authorize('update', $turma);
        Gate::authorize('create', [Turma::class, Oferecimento::findOrFail($request->oferecimento_id)]);
        $dados = $request->validated();
        unset($dados['taxas'], $dados['vagas']);
        $turma->fill($dados);
        $turma->user_id = auth()->id();
        $turma->save();
        $this->syncTaxas($turma, $request->validated('taxas', []));
        $this->syncVagas($turma, $request->validated('vagas', []));
        return $this->voltarParaOferecimento($turma);
    }

    public function destroy(Turma $turma)
    {
        Gate::authorize('delete', $turma);
        $oferecimento = $turma->oferecimento;
        $turma->delete();
        return $this->voltarParaOferecimento($turma, $oferecimento);
    }

    private function oferecimentosPermitidos()
    {
        return Oferecimento::whereHas('atividade', fn ($q) => $q->whereIn('tipo', auth()->user()->tiposPermitidos()))->get();
    }

    /**
     * As turmas são gerenciadas dentro da tela do oferecimento.
     */
    private function voltarParaOferecimento(Turma $turma, ?Oferecimento $oferecimento = null)
    {
        $oferecimento = $oferecimento ?: $turma->oferecimento;

        if ($oferecimento) {
            return redirect("/oferecimentos/{$oferecimento->atividade_id}/{$oferecimento->id}");
        }

        return redirect('/turmas');
    }

    private function syncVagas(Turma $turma, array $vagas): void
    {
        $config = $turma->load('oferecimento.atividade')->configuracaoVagas();
        $perfis = array_merge($config['perfis'], $config['reservadas'] ? [VagaTurma::RESERVADA] : []);
        $tamanhos = array_map('strval', array_keys($config['tamanhos']));

        $vagas_id = [];
        foreach ($vagas as $perfil => $valor) {
            foreach (is_array($valor) ? $valor : ['' => $valor] as $tamanho => $quantidade) {
                $tamanho = (string) $tamanho;
                if ($quantidade === null || $quantidade === '' || !in_array($perfil, $perfis)) {
                    continue;
                }
                if ($tamanhos ? !in_array($tamanho, $tamanhos, true) : $tamanho !== '') {
                    continue;
                }
                $model = $turma->vagas()->updateOrCreate([
                    'perfil' => $perfil,
                    'tamanho' => $tamanho,
                ], [
                    'quantidade' => $quantidade,
                ]);
                $vagas_id[] = $model->id;
            }
        }

        $turma->vagas()->whereNotIn('id', $vagas_id)->delete();
    }

    private function syncTaxas(Turma $turma, array $taxas): void
    {
        $permiteVinculo = in_array($turma->load('oferecimento.atividade')->oferecimento?->atividade?->tipo, config('cepe.taxa_vinculo.tipos'));

        $taxas_id = [];
        foreach ($taxas as $taxa) {
            if (!$permiteVinculo && !empty($taxa['vinculo'])) {
                continue;
            }
            if (!isset($taxa['valor']) || $taxa['valor'] === '' || !is_numeric($taxa['valor'])) {
                continue;
            }
            $model = $turma->taxas()->updateOrCreate([
                'perfil' => $taxa['perfil'],
                'periodo_inscricao' => $taxa['periodo_inscricao'],
                'vinculo' => $taxa['vinculo'] ?? '',
            ], [
                'valor' => $taxa['valor'],
            ]);
            $taxas_id[] = $model->id;
        }

        $turma->taxas()->whereNotIn('id', $taxas_id)->delete();
    }
}
