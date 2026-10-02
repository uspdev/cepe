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
        Gate::authorize('admin');
        if($request->has('search')){
            $turmas = Turma::where('oferecimento_id','like','%'.$request->search.'%')->get();
        } else {
            $turmas = Turma::all();
        }

        return view('turmas.index',[
            'turmas' => $turmas
        ]);
    }

    public function create(){
        Gate::authorize('admin');
        return view('turmas.create',[
            'oferecimentos' => Oferecimento::all()
        ]);
    }

    public function store(TurmaRequest $request){
        Gate::authorize('admin');
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
        Gate::authorize('admin');
        return view('turmas.show',[
            'turma' => $turma
        ]);
    }

    public function edit(Turma $turma){
        Gate::authorize('admin');
        return view('turmas.edit',[
            'turma' => $turma,
            'oferecimentos' => Oferecimento::all()
        ]);
    }

    public function update(TurmaRequest $request, Turma $turma){
        Gate::authorize('admin');
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
        Gate::authorize('admin');
        $oferecimento = $turma->oferecimento;
        $turma->delete();
        return $this->voltarParaOferecimento($turma, $oferecimento);
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
        $taxas_id = [];
        foreach ($taxas as $taxa) {
            if (empty($taxa['valor']) || !is_numeric($taxa['valor'])) {
                continue;
            }
            $model = $turma->taxas()->updateOrCreate([
                'perfil' => $taxa['perfil'],
                'periodo_inscricao' => $taxa['periodo_inscricao'],
            ], [
                'valor' => $taxa['valor'],
            ]);
            $taxas_id[] = $model->id;
        }

        $turma->taxas()->whereNotIn('id', $taxas_id)->delete();
    }
}
