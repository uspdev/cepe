<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Oferecimento;
use App\Models\Atividade;

use App\Http\Requests\OferecimentoRequest;

class OferecimentoController extends Controller
{
    public function index(Request $request){
        if($request->has('search')){
            $oferecimentos = Oferecimento::where('atividade_id','like','%'.$request->search.'%')->get();
        } else {
            $oferecimentos = Oferecimento::all();
        }

        return view('oferecimentos.index',[
            'oferecimentos' => $oferecimentos
        ]);
    }

    public function create(Atividade $atividade){
        return view('oferecimentos.create',[
                'atividade' => $atividade
            ]
        );
    }

    public function store(OferecimentoRequest $request){
        $oferecimento = new Oferecimento;
        $oferecimento->atividade_id = $request->atividade_id;
        
        $oferecimento->user_id = auth()->id();
        $oferecimento->save();
        return redirect("/atividades/{$request->atividade_id}");
    }

    public function show(Atividade $atividade, Oferecimento $oferecimento){
        return view('oferecimentos.show',[
            'atividade' => $atividade,
            'oferecimento' => $oferecimento
        ]);
    }

    public function edit(Atividade $atividade, Oferecimento $oferecimento){
        return view('oferecimentos.edit',[
            'atividade' => $atividade,
            'oferecimento' => $oferecimento
        ]);
    }

    public function update(OferecimentoRequest $request, Atividade $atividade, Oferecimento $oferecimento){
        $oferecimento->atividade_id = $request->atividade_id;

        $oferecimento->user_id = auth()->id();
        $oferecimento->save();
        return redirect("/oferecimentos/{$atividade->id}/{$oferecimento->id}");
    }

    public function destroy(Atividade $atividade, Oferecimento $oferecimento)
    {
        $oferecimento->delete();
        return redirect("/atividades/{$atividade->id}");
    }
}