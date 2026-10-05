<?php

namespace App\Http\Controllers\Atestados;

use App\Http\Controllers\Controller;
use App\Http\Requests\AtestadoRequest;
use App\Models\Atestado;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MeusAtestadosController extends Controller
{
    public function index()
    {
        return view('meus-atestados.index', [
            'atestados' => auth()->user()->atestados()->latest()->get(),
        ]);
    }

    public function create()
    {
        if (request('tipo') === 'parq' && ! array_key_exists('parq', auth()->user()->tiposAtestado())) {
            $faixa = config('cepe.atestados.parq');

            return redirect('/meus-atestados/create?tipo=atestado_medico')
                ->with('alert-warning', "O PAR-Q é destinado a pessoas de {$faixa['idade_min']} a {$faixa['idade_max']} anos. Envie um atestado médico.");
        }

        return view('meus-atestados.create', ['tipos' => auth()->user()->tiposAtestado()]);
    }

    public function store(AtestadoRequest $request)
    {
        $dados = $request->validated();

        DB::transaction(function () use ($request, $dados) {
            $atestado = Atestado::create([
                'user_id' => auth()->id(),
                'tipo' => $dados['tipo'],
                'emitido_em' => $dados['tipo'] === 'parq' ? now()->toDateString() : Carbon::createFromFormat('d/m/Y', $dados['emitido_em'])->toDateString(),
                'arquivo' => $request->hasFile('arquivo') ? $request->file('arquivo')->store('atestados', 'local') : null,
                'status' => $dados['tipo'] === 'parq' ? 'aprovado' : 'em_analise',
            ]);

            if ($dados['tipo'] === 'parq') {
                $atestado->parq()->create([
                    'respostas' => $dados['respostas'],
                    'termo_aceito' => $request->boolean('termo_aceito'),
                    'aceite_ip' => $request->ip(),
                    'aceito_em' => now(),
                ]);
            }
        });

        return redirect('/meus-atestados')->with('success', 'Documento enviado e aguardando análise.');
    }
}
