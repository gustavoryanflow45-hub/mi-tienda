<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Login del panel admin (/admin/login), con el guard 'admin'.
 *
 * Solo busca en la tabla admins: una cuenta de la tienda, sea cual sea su
 * rol, recibe el mismo error que una contraseña equivocada, para no
 * confirmar qué correos son de administradores. No ofrece "recordarme".
 */
class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::guard('admin')->check()) {
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

        if (! Auth::guard('admin')->attempt($credentials)) {
            // Sin actor: no hay sesión. El correo intentado sirve para ver
            // ataques dirigidos a una cuenta.
            $audit->record('admin.login_failed', null, ['email' => Str::lower($credentials['email'])]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('Correo o contraseña incorrectos.')]);
        }

        $request->session()->regenerate();

        $admin = Auth::guard('admin')->user();
        $admin->forceFill(['last_login_at' => now()])->save();
        $audit->record('admin.login', null, [], $admin);

        return redirect()->intended(route('admin.home'));
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
