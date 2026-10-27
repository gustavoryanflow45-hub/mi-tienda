<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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

    public function login(Request $request, AuditLog $audit)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attemptWhen($credentials, fn ($user) => $user->isAdmin())) {
            // Sin actor: no hay sesión. El correo intentado sirve para ver
            // ataques dirigidos a una cuenta.
            $audit->record('admin.login_failed', null, ['email' => Str::lower($credentials['email'])]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('Correo o contraseña incorrectos.')]);
        }

        $request->session()->regenerate();
        $audit->record('admin.login');

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
