<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginLocalController extends Controller
{
    public function create()
    {
        return view('auth.entrar');
    }

    public function store(Request $request)
    {
        $credenciais = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $credenciais['email'] = mb_strtolower(trim($credenciais['email']));

        // só contas locais; usuários da Senha Única entram pelo OAuth
        if (! Auth::attempt($credenciais + ['local' => true], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'E-mail ou senha inválidos.']);
        }

        $request->session()->regenerate();
        $request->user()->concederAcessoBasico();

        return redirect()->intended('/');
    }
}
