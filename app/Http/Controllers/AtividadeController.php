<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Atividade;
use App\Http\Requests\AtividadeRequest;

class AtividadeController extends Controller
{
    public function index(Request $request){
        if($request->has('search')){
            $atividades = Atividade::where('nome','like','%'.$request->search.'%')->get();
        } else {
            $atividades = Atividade::all();
        }

        return view('atividades.index',[
            'atividades' => $atividades
        ]);
    }

    public function create(){
        return view('atividades.create');
    }

    public function store(AtividadeRequest $request){
        $atividade = new Atividade;
        $atividade->nome = $request->nome;
        $atividade->descricao = $request->descricao;
        
        $atividade->user_id = auth()->id();
        $atividade->save();
        return redirect('/atividades');
    }

    public function show(Atividade $atividade){
        return view('atividades.show',[
            'atividade' => $atividade
        ]);
    }

    public function edit(Atividade $atividade){
        return view('atividades.edit',[
            'atividade' => $atividade
        ]);
    }

    public function update(AtividadeRequest $request, Atividade $atividade){
        $atividade->nome = $request->nome;
        $atividade->descricao = $request->descricao;
        
        $atividade->user_id = auth()->id();
        $atividade->save();
        return redirect("/atividades/{$atividade->id}");
    }

    public function destroy(Atividade $atividade)
    {
        $atividade->delete();
        return redirect('/atividades');
    }
}