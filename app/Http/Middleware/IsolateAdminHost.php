<?php

namespace App\Http\Middleware;

use App\Support\AdminHost;
use Closure;
use Illuminate\Http\Request;

/**
 * Aísla el host admin del de la tienda. Va el primero del grupo 'web', antes
 * de StartSession, porque cambia la configuración de la cookie de sesión.
 *
 * En el host admin:
 *  - La sesión usa su propia cookie (config('admin.session_cookie')), ligada
 *    al host (sin Domain) y SameSite=strict. Aunque alguien ponga
 *    SESSION_DOMAIN=.dominio.com para la tienda, la cookie del admin nunca
 *    viaja a dominio.com, y la de la tienda no abre el panel.
 *  - Solo responden las rutas del grupo admin. Un GET a una ruta de la tienda
 *    (los enlaces del layout común) se redirige a APP_URL; cualquier otro
 *    verbo da 404.
 *
 * Sin ADMIN_DOMAIN no hace nada: en XAMPP tienda y panel comparten host y
 * cookie, y lo que separa el panel es el 2FA guardado en la sesión.
 */
class IsolateAdminHost
{
    /**
     * Rutas de la tienda que el layout común usa y que solo tocan la sesión
     * del propio host: el selector de idioma y el de moneda.
     */
    public const SHARED_ROUTES = ['language.change', 'currency.change'];

    public function handle(Request $request, Closure $next)
    {
        if (! AdminHost::matches($request)) {
            return $next($request);
        }

        config([
            'session.cookie' => config('admin.session_cookie'),
            'session.domain' => null,
            'session.same_site' => 'strict',
        ]);

        $route = $request->route();

        if ($route
            && strcasecmp((string) $route->getDomain(), AdminHost::domain()) !== 0
            && ! $route->named(self::SHARED_ROUTES)) {
            if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
                return redirect()->away(rtrim(config('app.url'), '/').$request->getRequestUri());
            }

            abort(404);
        }

        return $next($request);
    }
}
