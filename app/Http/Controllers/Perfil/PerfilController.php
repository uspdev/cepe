<?php

namespace App\Http\Controllers\Perfil;

use App\Http\Controllers\Controller;
use App\Http\Requests\PerfilRequest;
use App\Models\Perfil;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

class PerfilController extends Controller
{
    public function edit(?User $user = null)
    {
        $user ??= auth()->user();
        Gate::authorize('update', $user);

        return view('perfil.edit', ['alvo' => $user, 'perfil' => $user->perfilOuNovo(), 'proprio' => $user->is(auth()->user())]);
    }

    public function update(PerfilRequest $request, ?User $user = null)
    {
        $alvo = $request->alvo();
        $dados = $request->safe()->only(Perfil::CAMPOS_USUARIO);
        $dados['data_nascimento'] = Carbon::createFromFormat('d/m/Y', $request->validated('data_nascimento'))->toDateString();

        if ($dados['sem_cpf']) {
            $dados['cpf'] = null;
        }
        if ($dados['sem_rg']) {
            $dados['rg'] = null;
        }
        if (Gate::allows('admin')) {
            $admin = $request->safe()->only(Perfil::CAMPOS_ADMIN);
            $admin['credito'] = (int) ($admin['credito'] ?? 0);
            $dados += $admin;
        }

        if ($alvo->local) {
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
