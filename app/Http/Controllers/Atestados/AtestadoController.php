<?php

namespace App\Http\Controllers\Atestados;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnaliseAtestadoRequest;
use App\Models\Atestado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AtestadoController extends Controller
{
    private function consulta(Request $request)
    {
        $status = $request->query('status', 'em_analise');
        $query = Atestado::with('user')->latest();

        $hoje = now()->toDateString();

        switch ($status) {
            case 'vencido':
                $query->where(fn ($q) => $q->where('status', 'vencido')->orWhere(fn ($q) => $q->where('status', 'aprovado')->whereDate('valido_ate', '<', $hoje)));
                break;
            case 'aprovado':
                $query->where('status', 'aprovado')->whereDate('valido_ate', '>=', $hoje);
                break;
            case 'todos':
                break;
            default:
                $query->where('status', $status);
                break;
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->query('tipo'));
        }
        if ($request->filled('search')) {
            $busca = '%'.addcslashes($request->query('search'), '%_\\').'%';
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', $busca)->orWhere('email', 'like', $busca));
        }

        return $query;
    }

    public function index(Request $request)
    {
        Gate::authorize('admin');

        return view('atestados.index', ['atestados' => $this->consulta($request)->paginate(50)->withQueryString()]);
    }

    public function show(Atestado $atestado)
    {
        Gate::authorize('admin');
        $atestado->load('user', 'parq', 'analisador');

        return view('atestados.show', ['atestado' => $atestado]);
    }

    public function analise(AnaliseAtestadoRequest $request, Atestado $atestado)
    {
        Gate::authorize('admin');
        $dados = $request->validated();
        $aprovado = $dados['status'] === 'aprovado';

        $atestado->update([
            'status' => $dados['status'],
            'observacao' => $dados['observacao'] ?? null,
            'valido_ate' => $aprovado ? $atestado->emitido_em->copy()->addMonths($atestado->validadeMeses()) : null,
        ]);
        $atestado->analisado_por = auth()->id();
        $atestado->analisado_em = now();
        $atestado->save();

        return redirect("/atestados/{$atestado->id}")->with('success', 'Análise registrada.');
    }

    public function arquivo(Atestado $atestado)
    {
        Gate::authorize('view', $atestado);
        abort_unless($atestado->arquivo && Storage::disk('local')->exists($atestado->arquivo), 404);

        return Storage::disk('local')->download($atestado->arquivo);
    }

    public function exportar(Request $request)
    {
        Gate::authorize('admin');
        $atestados = $this->consulta($request)->get();

        return response()->streamDownload(function () use ($atestados) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Usuário', 'E-mail', 'Tipo', 'Emissão', 'Validade', 'Status'], ';');
            foreach ($atestados as $a) {
                fputcsv($out, [$a->user?->name, $a->user?->email, $a->tipo_nome, $a->emitido_em->format('d/m/Y'), $a->valido_ate?->format('d/m/Y'), $a->status_nome], ';');
            }
            fclose($out);
        }, 'atestados-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function destroy(Atestado $atestado)
    {
        Gate::authorize('admin');
        if ($atestado->arquivo) {
            Storage::disk('local')->delete($atestado->arquivo);
        }
        $atestado->delete();

        return redirect('/atestados')->with('success', 'Documento removido.');
    }
}
