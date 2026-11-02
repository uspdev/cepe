<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Atividade;
use App\Http\Requests\AtividadeRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AtividadeController extends Controller
{
    public function index(Request $request){
        Gate::authorize('viewAny', Atividade::class);
        $atividades = Atividade::where('status', '!=', 'excluido')
            ->whereIn('tipo', $request->user()->tiposPermitidos())
            ->when($request->filled('search'), fn($q) => $q->where(fn($w) =>
                $w->where('nome', 'like', "%{$request->search}%")
                  ->orWhere('descricao', 'like', "%{$request->search}%")))
            ->orderBy('nome')
            ->get();

        return view('atividades.index',[
            'atividades' => $atividades
        ]);
    }

    public function create(){
        Gate::authorize('create', Atividade::class);
        return view('atividades.create');
    }

    public function store(AtividadeRequest $request){
        Gate::authorize('create', Atividade::class);
        $atividade = new Atividade;
        $atividade->nome = $request->nome;
        $atividade->descricao = $request->descricao;
        $atividade->tipo = $request->tipo;
        $atividade->status = $request->status;

        $atividade->user_id = Auth::id();
        $atividade->save();
        session()->flash('alert-success', 'Atividade cadastrada com sucesso.');
        return redirect('/atividades');
    }

    public function show(Atividade $atividade){
        Gate::authorize('view', $atividade);
        return view('atividades.show',[
            'atividade' => $atividade
        ]);
    }

    public function edit(Atividade $atividade){
        Gate::authorize('update', $atividade);
        return view('atividades.edit',[
            'atividade' => $atividade
        ]);
    }

    public function update(AtividadeRequest $request, Atividade $atividade){
        Gate::authorize('update', $atividade);
        $atividade->nome = $request->nome;
        $atividade->descricao = $request->descricao;
        $atividade->tipo = $request->tipo;
        $atividade->status = $request->status;

        $atividade->user_id = Auth::id();
        $atividade->save();
        session()->flash('alert-success', 'Atividade atualizada com sucesso.');
        return redirect("/atividades/{$atividade->id}");
    }

    public function destroy(Atividade $atividade)
    {
        Gate::authorize('delete', $atividade);
        $atividade->update(['status' => 'excluido']);
        session()->flash('alert-success', 'Atividade excluída com sucesso.');
        return redirect('/atividades');
    }
}