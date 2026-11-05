<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Alias 'api.active', detrás de 'auth:sanctum' en las rutas con token. Un
 * token emitido antes de bloquear la cuenta (users.banned) deja de valer en
 * su siguiente petición: se revoca y se responde 403 account_disabled.
 */
class EnsureApiUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user?->banned) {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'message' => __('Esta cuenta está bloqueada. Contacta con soporte.'),
                'code' => 'account_disabled',
            ], 403);
        }

        return $next($request);
    }
}
