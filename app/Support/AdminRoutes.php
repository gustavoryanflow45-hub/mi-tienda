<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * Registra routes/admin.php: grupo 'web', nombres 'admin.', prefijo
 * ADMIN_PATH y, con ADMIN_DOMAIN, ligado a ese host.
 *
 * Es el único sitio que lo hace: bootstrap/app.php lo llama al cargar las
 * rutas y los tests que cambian config('admin.*') lo vuelven a llamar.
 */
class AdminRoutes
{
    /**
     * Prefijo de las rutas del panel. Sin dominio propio no puede quedar
     * vacío: el panel colgaría de / y pisaría las rutas de la tienda.
     */
    public static function prefix(): string
    {
        $path = trim((string) config('admin.path'), '/');

        return $path === '' && ! AdminHost::domain() ? 'admin' : $path;
    }

    public static function register(): void
    {
        $routes = Route::middleware('web')->name('admin.');

        if ($prefix = self::prefix()) {
            $routes->prefix($prefix);
        }

        if ($domain = AdminHost::domain()) {
            $routes->domain($domain);
        }

        $routes->group(base_path('routes/admin.php'));
    }
}
