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

    public function store(OferecimentoRequest $request)
    {
        // 1. Cria e salva o Oferecimento
        $oferecimento = new Oferecimento;
        $oferecimento->atividade_id = $request->atividade_id;
        $oferecimento->pagamento = $request->pagamento;
        $oferecimento->periodo_semestre = $request->periodo_semestre;
        $oferecimento->periodo_ano = $request->periodo_ano;
        $oferecimento->atestado_medico = $request->boolean('atestado_medico');
        $oferecimento->exame_dermatologico = $request->boolean('exame_dermatologico');
        $oferecimento->user_id = auth()->id();
        $oferecimento->save();

        // 2. Salva os períodos de inscrição por perfil que foram ativados
        if ($request->has('periodos') && is_array($request->periodos)) {
            foreach ($request->periodos as $perfilKey => $dados) {
                if (!empty($dados['ativo']) && !empty($dados['inicio']) && !empty($dados['fim'])) {
                    $oferecimento->periodos()->create([
                        'perfil' => $perfilKey,
                        'inicio' => $dados['inicio'],
                        'fim'    => $dados['fim'],
                    ]);
                }
            }
        }

        return redirect("/atividades/{$request->atividade_id}")
            ->with('success', 'Oferecimento criado com sucesso!');
    }

    public function show(Atividade $atividade, Oferecimento $oferecimento){
        return view('oferecimentos.show',[
            'atividade' => $atividade,
            'oferecimento' => $oferecimento
        ]);
    }

    public function edit(Oferecimento $oferecimento){
        return view('oferecimentos.edit',[
            'oferecimento' => $oferecimento
        ]);
    }

    public function update(OferecimentoRequest $request, Oferecimento $oferecimento){
        $oferecimento->atividade_id = $request->atividade_id;
        
        $oferecimento->user_id = auth()->id();
        $oferecimento->save();
        return redirect("/oferecimentos/{$oferecimento->id}");
    }

    public function destroy(Oferecimento $oferecimento)
    {
        $oferecimento->delete();
        return redirect('/oferecimentos');
    }
}