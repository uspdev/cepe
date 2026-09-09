<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Atividade;
use App\Http\Requests\AtividadeRequest;
use Illuminate\Support\Facades\Gate;

class AtividadeController extends Controller
{
    public function index(Request $request){
        Gate::authorize('admin');
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
        Gate::authorize('admin');
        return view('atividades.create');
    }

    public function store(AtividadeRequest $request){
        Gate::authorize('admin');
        $atividade = new Atividade;
        $atividade->nome = $request->nome;
        $atividade->descricao = $request->descricao;
        
        $atividade->user_id = auth()->id();
        $atividade->save();
        return redirect('/atividades');
    }

    public function show(Atividade $atividade){
        Gate::authorize('admin');
        return view('atividades.show',[
            'atividade' => $atividade
        ]);
    }

    public function edit(Atividade $atividade){
        Gate::authorize('admin');
        return view('atividades.edit',[
            'atividade' => $atividade
        ]);
    }

    public function update(AtividadeRequest $request, Atividade $atividade){
        Gate::authorize('admin');
        $atividade->nome = $request->nome;
        $atividade->descricao = $request->descricao;
        
        $atividade->user_id = auth()->id();
        $atividade->save();
        return redirect("/atividades/{$atividade->id}");
    }

    public function destroy(Atividade $atividade)
    {
        Gate::authorize('admin');
        $atividade->delete();
        return redirect('/atividades');
    }
}