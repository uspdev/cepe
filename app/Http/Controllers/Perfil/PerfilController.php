<?php

namespace App\Http\Controllers\Perfil;

use App\Http\Controllers\Controller;
use App\Http\Requests\PerfilRequest;
use App\Models\Perfil;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class PerfilController extends Controller
{
    public function index()
    {
        Gate::authorize('perfis.gerenciar');

        $users = User::orderBy('name')->get();
        return view('perfil.index', compact('users'));
    }
    
    public function edit(?User $user = null)
    {
        $user ??= auth()->user();
        Gate::authorize('update', $user);

        return view('perfil.edit', ['alvo' => $user, 'perfil' => $user->perfilOuNovo(), 'proprio' => $user->is(auth()->user())]);
    }

    public function update(PerfilRequest $request, ?User $user = null)
    {
        $alvo = $request->alvo();
        $dados = $request->dadosPerfil();

        if (Gate::allows('perfis.gerenciar')) {
            $admin = $request->safe()->only(Perfil::CAMPOS_ADMIN);
            $admin['credito'] = (int) ($admin['credito'] ?? 0);
            $dados += $admin;
            $perfilAtual = $alvo->perfil;
            $dados['vinculo_manual'] = filled($admin['vinculo_usp'] ?? null)
                && ($admin['vinculo_usp'] !== $perfilAtual?->vinculo_usp || (bool) $perfilAtual?->vinculo_manual);
        }

        // credenciais de conta local só o próprio usuário ou o admin alteram (o sru gerencia dados do perfil, não senhas)
        if ($alvo->local && ($alvo->is(auth()->user()) || Gate::allows('admin'))) {
            $alvo->name = $request->validated('name');
            $alvo->email = $request->validated('email');
            if ($request->filled('password')) {
                $alvo->password = $request->validated('password');
            }
            $alvo->save();
        }

        $alvo->perfil()->updateOrCreate([], $dados);

        return redirect($alvo->is(auth()->user()) ? '/perfil' : "/usuarios/{$alvo->id}/perfil")->with('success', 'Perfil atualizado.');
    }
}
