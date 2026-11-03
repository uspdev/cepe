<?php

namespace App\Http\Controllers;

use App\Models\Chamada;
use App\Models\Matricula;
use App\Models\Presenca;
use App\Models\Turma;
use App\Services\FrequenciaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FrequenciaController extends Controller
{
    public function minhasTurmas(Request $request)
    {
        $turmas = Turma::where('professor_id', $request->user()->id)
            ->with('oferecimento.atividade')
            ->get();

        return view('frequencia.minhas-turmas', ['turmas' => $turmas]);
    }

    public function index(Turma $turma)
    {
        Gate::authorize('frequencia', $turma);

        return view('frequencia.index', [
            'turma' => $turma,
            'chamadas' => $turma->chamadas()->withCount('presencas')->orderByDesc('data')->get(),
        ]);
    }

    public function minhaFrequencia(Request $request, FrequenciaService $frequencia)
    {
        $matriculas = $request->user()->matriculas()
            ->where('status', Matricula::MATRICULADA)
            ->with(['turma.oferecimento.atividade', 'presencas.chamada'])
            ->latest()
            ->get();

        return view('frequencia.minha-frequencia', [
            'matriculas' => $matriculas,
            'frequencia' => $frequencia,
        ]);
    }

    public function hoje(Request $request, Turma $turma, FrequenciaService $frequencia)
    {
        Gate::authorize('frequencia', $turma);

        return redirect()->route('frequencia.show', [$turma, $frequencia->chamadaDeHoje($turma, $request->user()->id)]);
    }

    public function show(Turma $turma, Chamada $chamada)
    {
        Gate::authorize('frequencia', $turma);
        abort_unless($chamada->turma_id === $turma->id, 404);

        return view('frequencia.show', [
            'turma' => $turma,
            'chamada' => $chamada,
            'matriculas' => $turma->matriculasAtivas()
                ->with(['user', 'presencas' => fn ($q) => $q->where('chamada_id', $chamada->id)])
                ->get()
                ->sortBy('user.name'),
        ]);
    }

    public function update(Request $request, Turma $turma, Chamada $chamada)
    {
        Gate::authorize('frequencia', $turma);
        abort_unless($chamada->turma_id === $turma->id, 404);
        $dados = $request->validate([
            'presencas' => 'required|array',
            'presencas.*' => 'in:' . implode(',', array_keys(config('cepe.status_presenca'))),
        ]);

        $matriculas = $turma->matriculasAtivas()->pluck('id')->all();
        $origem = $turma->professor_id === $request->user()->id ? Presenca::PROFESSOR : Presenca::GESTOR;

        foreach ($dados['presencas'] as $matricula => $status) {
            if (in_array($matricula, $matriculas, true)) {
                Presenca::updateOrCreate(
                    ['chamada_id' => $chamada->id, 'matricula_id' => $matricula],
                    ['status' => $status, 'origem' => $origem]
                );
            }
        }

        return redirect()->route('frequencia.show', [$turma, $chamada])->with('alert-success', 'Frequência salva.');
    }

    public function alterarData(Request $request, Turma $turma, Chamada $chamada)
    {
        Gate::authorize('frequencia', $turma);
        abort_unless($chamada->turma_id === $turma->id, 404);
        $dados = $request->validate(['data' => 'required|date_format:Y-m-d']);

        if ($turma->chamadas()->whereDate('data', $dados['data'])->where('id', '!=', $chamada->id)->exists()) {
            return back()->with('alert-danger', 'Já existe uma chamada registrada nesta data.');
        }

        $chamada->update(['data' => $dados['data']]);

        return redirect()->route('frequencia.show', [$turma, $chamada])->with('alert-success', 'Data da chamada corrigida.');
    }

    public function checkin(Matricula $matricula, FrequenciaService $frequencia)
    {
        Gate::authorize('checkin', $matricula);
        $turma = $matricula->turma;

        if (! $frequencia->janelaAberta($turma)) {
            return back()->with('alert-danger', 'O check-in só está disponível durante o horário da aula.');
        }

        $chamada = $frequencia->chamadaDeHoje($turma);
        $presenca = Presenca::firstOrCreate(
            ['chamada_id' => $chamada->id, 'matricula_id' => $matricula->id],
            ['status' => Presenca::PRESENTE, 'origem' => Presenca::ALUNO]
        );

        if (! $presenca->wasRecentlyCreated) {
            return back()->with('alert-info', 'Sua presença de hoje já foi registrada.');
        }

        return back()->with('alert-success', 'Presença registrada.');
    }
}
