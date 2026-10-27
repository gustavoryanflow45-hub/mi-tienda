<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Login del panel admin (/admin/login).
 *
 * En modo dominio la cookie de la tienda no llega al host admin, así que el
 * panel necesita su propio login. Solo admite cuentas admin; a cualquier otra
 * le responde el mismo error que a una contraseña equivocada, para no
 * confirmar qué correos son de administradores. No ofrece "recordarme".
 */
class LoginController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if ($request->user()?->isAdmin()) {
            return redirect()->route('admin.home');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attemptWhen($credentials, fn ($user) => $user->isAdmin())) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('Correo o contraseña incorrectos.')]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.home'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
