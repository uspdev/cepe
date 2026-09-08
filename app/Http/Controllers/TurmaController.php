<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Turma;
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
        return view('turmas.create');
    }

    public function store(TurmaRequest $request){
        $turma = new Turma;
        $turma->oferecimento_id = $request->oferecimento_id;
        
        $turma->user_id = auth()->id();
        $turma->save();
        return redirect('/turmas');
    }

    public function show(Turma $turma){
        return view('turmas.show',[
            'turma' => $turma
        ]);
    }

    public function edit(Turma $turma){
        return view('turmas.edit',[
            'turma' => $turma
        ]);
    }

    public function update(TurmaRequest $request, Turma $turma){
        $turma->oferecimento_id = $request->oferecimento_id;
        
        $turma->user_id = auth()->id();
        $turma->save();
        return redirect("/turmas/{$turma->id}");
    }

    public function destroy(Turma $turma)
    {
        $turma->delete();
        return redirect('/turmas');
    }
}