<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Turma;
use App\Models\Oferecimento;
use App\Http\Requests\TurmaRequest;

class TurmaController extends Controller
{
    public function index(Request $request){
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
        return view('turmas.create',[
            'oferecimentos' => Oferecimento::all()
        ]);
    }

    public function store(TurmaRequest $request){
        $turma = new Turma;
        $turma->fill($request->validated());
        $turma->user_id = auth()->id();
        $turma->save();
        return $this->voltarParaOferecimento($turma);
    }

    public function show(Turma $turma){
        return view('turmas.show',[
            'turma' => $turma
        ]);
    }

    public function edit(Turma $turma){
        return view('turmas.edit',[
            'turma' => $turma,
            'oferecimentos' => Oferecimento::all()
        ]);
    }

    public function update(TurmaRequest $request, Turma $turma){
        $turma->fill($request->validated());
        $turma->user_id = auth()->id();
        $turma->save();
        return $this->voltarParaOferecimento($turma);
    }

    public function destroy(Turma $turma)
    {
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
}
