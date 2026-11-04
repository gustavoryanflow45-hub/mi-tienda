<?php

use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Panel de administración
|--------------------------------------------------------------------------
|
| El panel (tiendas, liquidaciones, billetera admin y almacén) vive en su
| propio host para que un fallo en la tienda o en el panel de vendedores no
| alcance la sesión del admin.
|
| ADMIN_DOMAIN        Host del panel, p. ej. admin.gustore.com. Vacío = las
|                     rutas admin cuelgan de /ADMIN_PATH en el mismo host
|                     (XAMPP).
| ADMIN_PATH          Prefijo de las rutas del panel (por defecto admin). Con
|                     ADMIN_DOMAIN puede quedar vacío (admin.gustore.com/);
|                     sin dominio, vacío vuelve a admin para no pisar la
|                     tienda. Ver App\Support\AdminRoutes.
| ADMIN_ENFORCE_HOST  true = EnsureUserIsAdmin responde 404 a cualquier
|                     petición admin que no llegue por ADMIN_DOMAIN. Con el
|                     flag activo y sin dominio, el panel queda cerrado.
| ADMIN_REQUIRE_2FA   Exige el segundo factor (TOTP) antes de entrar.
| ADMIN_SESSION_COOKIE Nombre de la cookie de sesión en el host admin.
|
| Tras cambiar ADMIN_DOMAIN o ADMIN_PATH: php artisan config:clear y
| php artisan route:clear (route:cache hornea dominio y prefijo en las rutas).
*/

return [
    'domain' => env('ADMIN_DOMAIN') ?: null,

    'path' => trim((string) env('ADMIN_PATH', 'admin'), '/'),

    'enforce_host' => (bool) env('ADMIN_ENFORCE_HOST', false),

    'require_two_factor' => (bool) env('ADMIN_REQUIRE_2FA', true),

    'session_cookie' => env(
        'ADMIN_SESSION_COOKIE',
        Str::slug((string) env('APP_NAME', 'laravel')).'-admin-session'
    ),
];
