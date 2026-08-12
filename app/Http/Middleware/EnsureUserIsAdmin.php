<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsAdmin
{
    /**
     * Restringe una ruta a users.user_type = 'admin'.
     *
     * Va siempre después de 'auth': los invitados deben acabar en /login,
     * no en un 403.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check() || ! Auth::user()->isAdmin()) {
            abort(403, 'No autorizado.');
        }

        return $next($request);
    }
}
