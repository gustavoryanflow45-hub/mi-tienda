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
|                     rutas admin cuelgan de /admin en el mismo host (XAMPP).
|                     Las rutas llevan el prefijo /admin en ambos modos.
| ADMIN_ENFORCE_HOST  true = EnsureUserIsAdmin responde 404 a cualquier
|                     petición admin que no llegue por ADMIN_DOMAIN. Con el
|                     flag activo y sin dominio, el panel queda cerrado.
| ADMIN_REQUIRE_2FA   Exige el segundo factor (TOTP) antes de entrar.
| ADMIN_SESSION_COOKIE Nombre de la cookie de sesión en el host admin.
|
| Tras cambiar ADMIN_DOMAIN: php artisan config:clear && php artisan route:clear
| (route:cache hornea el dominio en las rutas).
*/

return [
    'domain' => env('ADMIN_DOMAIN') ?: null,

    'enforce_host' => (bool) env('ADMIN_ENFORCE_HOST', false),

    'require_two_factor' => (bool) env('ADMIN_REQUIRE_2FA', true),

    'session_cookie' => env(
        'ADMIN_SESSION_COOKIE',
        Str::slug((string) env('APP_NAME', 'laravel')).'-admin-session'
    ),
];
