<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Support\AdminHost;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsAdmin
{
    /**
     * Restringe una ruta a una cuenta del guard 'admin' (tabla admins).
     *
     * Va siempre después de 'auth:admin', que ya manda a los invitados al
     * login del panel; esto es la segunda comprobación. Un usuario de la
     * tienda, sea del rol que sea, no es un Admin y nunca pasa.
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

        if (! Auth::guard('admin')->user() instanceof Admin) {
            abort(403, __('No autorizado.'));
        }

        return $next($request);
    }
}
