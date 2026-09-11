<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureShopApproved
{
    /**
     * Exige que el seller tenga su tienda aprobada (shops.status = 1).
     *
     * /shops/create es público y auto-loguea, así que cualquiera obtiene
     * user_type = 'seller' al instante; sin esto podría publicar productos
     * sin que nadie revise su documentación.
     *
     * Los admin pasan siempre: no tienen tienda.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (! $user) {
            abort(403, 'No autorizado.');
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $shop = $user->shop;

        if (! $shop) {
            return redirect()->route('dashboard')
                ->with('warning', 'Necesitas registrar tu tienda antes de usar el panel de vendedor.');
        }

        if ((int) $shop->status !== 1) {
            $message = (int) $shop->status === 2
                ? 'Tu tienda fue rechazada. Contacta con soporte para más información.'
                : 'Tu tienda todavía está pendiente de aprobación. Te avisaremos en cuanto sea revisada.';

            return redirect()->route('dashboard')->with('warning', $message);
        }

        return $next($request);
    }
}
