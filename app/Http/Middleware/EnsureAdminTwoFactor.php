<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Exige que el admin haya pasado el segundo factor en ESTA sesión.
 *
 * Va después de 'auth' y 'admin'. La marca se guarda en la sesión (no en
 * users) con el id del admin: si en el mismo navegador entra otra cuenta, la
 * marca no le sirve. Caduca con la sesión, y el logout la borra.
 *
 * Un admin sin 2FA inscrito acaba en la pantalla del desafío, que le explica
 * cómo pedir el enlace de alta (php artisan admin:2fa-invite).
 */
class EnsureAdminTwoFactor
{
    public const SESSION_KEY = 'admin_two_factor_user_id';

    public function handle(Request $request, Closure $next)
    {
        if (! config('admin.require_two_factor')) {
            return $next($request);
        }

        if ((int) $request->session()->get(self::SESSION_KEY) === (int) $request->user()?->id) {
            return $next($request);
        }

        return redirect()->guest(route('admin.two-factor.challenge'));
    }
}
