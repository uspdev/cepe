<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\Oferecimento;

class CatalogoController extends Controller
{
    public function cursos()
    {
        return $this->index('cursos');
    }

    public function eventos()
    {
        return $this->index('eventos');
    }

    private function index(string $grupo)
    {
        $atividades = Atividade::where('status', 'ativo')
            ->whereIn('tipo', config('cepe.grupos_catalogo.' . $grupo, []))
            ->orderBy('nome')
            ->get()
            ->map(fn ($a) => [$a, Oferecimento::vigentePara($a)])
            ->filter(fn ($par) => $par[1] !== null);

        return view('catalogo.index', [
            'atividades' => $atividades,
            'grupo' => $grupo,
        ]);
    }

    public function show(Atividade $atividade)
    {
        abort_if($atividade->status !== 'ativo', 404);

        $oferecimento = Oferecimento::vigentePara($atividade);
        $oferecimento?->loadMissing(['periodos', 'turmas.vagas', 'turmas.taxas', 'turmas.matriculas']);

        return view('catalogo.show', [
            'atividade' => $atividade,
            'oferecimento' => $oferecimento,
        ]);
    }
}
