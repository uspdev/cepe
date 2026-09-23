<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Oferecimento;
use App\Models\Atividade;

use App\Http\Requests\OferecimentoRequest;
use Illuminate\Support\Facades\Gate;

class OferecimentoController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('admin');
        if ($request->has('search')) {
            $oferecimentos = Oferecimento::where('atividade_id', 'like', '%' . $request->search . '%')->get();
        } else {
            $oferecimentos = Oferecimento::all();
        }

        return view('oferecimentos.index', [
            'oferecimentos' => $oferecimentos
        ]);
    }

    public function create(Atividade $atividade)
    {
        Gate::authorize('admin');
        return view(
            'oferecimentos.create',
            [
                'atividade' => $atividade
            ]
        );
    }

    public function store(OferecimentoRequest $request)
    {
        Gate::authorize('admin');
        // 1. Cria e salva o Oferecimento
        $oferecimento = new Oferecimento;
        $oferecimento->atividade_id = $request->atividade_id;
        $oferecimento->pagamento = serialize($request->formas_pagamento);
        $oferecimento->periodo_semestre = $request->periodo_semestre;
        $oferecimento->periodo_ano = $request->periodo_ano;
        $oferecimento->atestado_medico = $request->boolean('atestado_medico');
        $oferecimento->exame_dermatologico = $request->boolean('exame_dermatologico');
        $oferecimento->user_id = auth()->id();
        $oferecimento->save();

        // 2. Salva os períodos de inscrição por perfil que foram ativados
        if ($request->has('periodos') && is_array($request->periodos)) {
            foreach ($request->periodos as $perfilKey => $dados) {
                if (!empty($dados['ativo']) && !empty($dados['inicio_data']) && !empty($dados['fim_data'])) {
                    $oferecimento->periodos()->create([
                        'perfil' => $perfilKey,
                        'inicio' => \Carbon\Carbon::createFromFormat('d/m/Y H:i', "{$dados['inicio_data']} " . ($dados['inicio_horario'] ?? '00:00')),
                        'fim'    => \Carbon\Carbon::createFromFormat('d/m/Y H:i', "{$dados['fim_data']} " . ($dados['fim_horario'] ?? '00:00')),
                    ]);
                }
            }
        }

        return redirect("/atividades/{$request->atividade_id}")
            ->with('success', 'Oferecimento criado com sucesso!');
    }

    public function show(Atividade $atividade, Oferecimento $oferecimento)
    {
        Gate::authorize('admin');
        return view('oferecimentos.show', [
            'atividade' => $atividade,
            'oferecimento' => $oferecimento
        ]);
    }

    public function edit(Atividade $atividade, Oferecimento $oferecimento)
    {
        Gate::authorize('admin');
        return view('oferecimentos.edit', [
            'atividade' => $atividade,
            'oferecimento' => $oferecimento
        ]);
    }

    public function update(OferecimentoRequest $request, Atividade $atividade, Oferecimento $oferecimento)
    {
        Gate::authorize('admin');

        $oferecimento->atividade_id = $request->atividade_id;
        $oferecimento->pagamento = serialize($request->formas_pagamento);
        $oferecimento->periodo_semestre = $request->periodo_semestre;
        $oferecimento->periodo_ano = $request->periodo_ano;
        $oferecimento->atestado_medico = $request->boolean('atestado_medico');
        $oferecimento->exame_dermatologico = $request->boolean('exame_dermatologico');
        $oferecimento->user_id = auth()->id();
        $oferecimento->save();
        
        if ($request->has('periodos') && is_array($request->periodos)) {
            foreach ($request->periodos as $perfilKey => $dados) {
                if (!empty($dados['ativo']) && !empty($dados['inicio_data']) && !empty($dados['fim_data'])) {
                    $oferecimento->periodos()->updateOrCreate([
                        'perfil' => $perfilKey,
                    ], [
                        'inicio' => \Carbon\Carbon::createFromFormat('d/m/Y H:i', "{$dados['inicio_data']} " . ($dados['inicio_horario'] ?? '00:00')),
                        'fim'    => \Carbon\Carbon::createFromFormat('d/m/Y H:i', "{$dados['fim_data']} " . ($dados['fim_horario'] ?? '00:00')),
                    ]);
                }
            }
        }

        return redirect("/oferecimentos/{$atividade->id}/{$oferecimento->id}");
    }

    public function destroy(Atividade $atividade, Oferecimento $oferecimento)
    {
        Gate::authorize('admin');
        $oferecimento->delete();
        return redirect("/atividades/{$atividade->id}");
    }
}
