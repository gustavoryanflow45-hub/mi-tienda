<?php

namespace App\Http\Middleware;

use App\Support\AdminHost;
use App\Support\AdminRoutes;
use Closure;
use Illuminate\Http\Request;

/**
 * Da al panel admin su propia sesión. Va el primero del grupo 'web', antes
 * de StartSession, porque cambia la configuración de la cookie de sesión.
 *
 * En el host admin (ADMIN_DOMAIN):
 *  - La sesión usa su propia cookie (config('admin.session_cookie')), ligada
 *    al host (sin Domain) y SameSite=strict. Aunque alguien ponga
 *    SESSION_DOMAIN=.dominio.com para la tienda, la cookie del admin nunca
 *    viaja a dominio.com, y la de la tienda no abre el panel.
 *  - Solo responden las rutas del grupo admin. Un GET a una ruta de la tienda
 *    (los enlaces del layout común) se redirige a APP_URL; cualquier otro
 *    verbo da 404.
 *
 * Sin ADMIN_DOMAIN (XAMPP) las rutas admin.* usan esa misma cookie propia,
 * limitada además a /ADMIN_PATH: el navegador no la manda a la tienda, y
 * cerrar sesión en la tienda no cierra el panel ni al revés.
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
            if ($request->route()?->named('admin.*')) {
                return $this->withAdminCookie('/'.AdminRoutes::prefix(), $request, $next);
            }

            return $next($request);
        }

        $route = $request->route();

        if ($route
            && strcasecmp((string) $route->getDomain(), AdminHost::domain()) !== 0
            && ! $route->named(self::SHARED_ROUTES)) {
            if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
                return redirect()->away(rtrim(config('app.url'), '/').$request->getRequestUri());
            }

            abort(404);
        }

        return $this->withAdminCookie('/', $request, $next);
    }

    /**
     * Cambia la cookie de sesión solo durante esta petición: StartSession
     * (dentro de $next) ya la habrá escrito en la respuesta al volver. Si el
     * mismo proceso atiende otra petición (tests, Octane), la tienda no
     * hereda la cookie del panel.
     *
     * El SessionManager guarda el Store con el nombre de cookie con que lo
     * creó, y StartSession lee el nombre del Store (el resto, de la config):
     * por eso se cambia también en el Store, y se restaura igual.
     */
    protected function withAdminCookie(string $path, Request $request, Closure $next)
    {
        $keys = ['session.cookie', 'session.path', 'session.domain', 'session.same_site'];
        $original = array_combine($keys, array_map(fn ($key) => config($key), $keys));
        $store = app('session')->driver();
        $originalName = $store->getName();

        config([
            'session.cookie' => config('admin.session_cookie'),
            'session.path' => $path,
            'session.domain' => null,
            'session.same_site' => 'strict',
        ]);
        $store->setName(config('admin.session_cookie'));

        try {
            return $next($request);
        } finally {
            config($original);
            $store->setName($originalName);
        }
    }
}
