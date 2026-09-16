<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Matricula;
use App\Http\Requests\MatriculaRequest;

class MatriculaController extends Controller
{
    public function index(Request $request){
        if($request->has('search')){
            $matriculas = Matricula::where('a','like','%'.$request->search.'%')->get();
        } else {
            $matriculas = Matricula::all();
        }

        return view('matriculas.index',[
            'matriculas' => $matriculas
        ]);
    }

    public function create(){
        return view('matriculas.create');
    }

    public function store(MatriculaRequest $request){
        $matricula = new Matricula;
        $matricula->a = $request->a;
        
        $matricula->user_id = auth()->id();
        $matricula->save();
        return redirect('/matriculas');
    }

    public function show(Matricula $matricula){
        return view('matriculas.show',[
            'matricula' => $matricula
        ]);
    }

    public function edit(Matricula $matricula){
        return view('matriculas.edit',[
            'matricula' => $matricula
        ]);
    }

    public function update(MatriculaRequest $request, Matricula $matricula){
        $matricula->a = $request->a;
        
        $matricula->user_id = auth()->id();
        $matricula->save();
        return redirect("/matriculas/{$matricula->id}");
    }

    public function destroy(Matricula $matricula)
    {
        $matricula->delete();
        return redirect('/matriculas');
    }
}