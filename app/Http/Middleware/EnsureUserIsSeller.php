<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Restringe una ruta a users.user_type = 'seller' (alias 'seller').
 *
 * Va en todo routes/seller.php, detrás de 'auth' y 'shop.approved'; esta
 * última ya manda al dashboard a quien no tiene tienda aprobada, así que
 * aquí solo llega un rol inesperado (p. ej. una fila vieja con
 * user_type = 'admin' que tuviera tienda). Las cuentas del panel son otro
 * guard y nunca pasan por aquí.
 */
class EnsureUserIsSeller
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()?->isSeller()) {
            abort(403, __('No autorizado.'));
        }

        return $next($request);
    }
}
