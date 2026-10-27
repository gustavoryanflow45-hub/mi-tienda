<?php

namespace App\Http\Middleware;

use App\Support\AdminHost;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsAdmin
{
    /**
     * Restringe una ruta a users.user_type = 'admin'.
     *
     * Va siempre después de 'auth': los invitados deben acabar en el login,
     * no en un 403.
     *
     * Con ADMIN_ENFORCE_HOST además exige que la petición llegue por
     * ADMIN_DOMAIN, y responde 404 (no 403) para no confirmar que la ruta
     * existe. Si el flag está activo y no hay dominio configurado, el panel
     * queda cerrado: falla en cerrado, no en abierto.
     */
    public function handle(Request $request, Closure $next)
    {
        if (config('admin.enforce_host') && ! AdminHost::matches($request)) {
            abort(404);
        }

        if (! Auth::check() || ! Auth::user()->isAdmin()) {
            abort(403, __('No autorizado.'));
        }

        return $next($request);
    }
}
