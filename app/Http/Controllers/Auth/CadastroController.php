<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\CadastroRequest;
use App\Models\Perfil;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CadastroController extends Controller
{
    public function create()
    {
        return view('auth.cadastro', ['perfil' => new Perfil]);
    }

    public function store(CadastroRequest $request)
    {
        $user = DB::transaction(function () use ($request) {
            $user = (new User)->forceFill([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'password' => $request->validated('password'),
                'local' => true,
            ]);
            $user->save();
            $user->perfil()->create($request->dadosPerfil());
            $user->concederAcessoBasico();

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/')->with('success', 'Conta criada com sucesso.');
    }
}
