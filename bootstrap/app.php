<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // API móvil: /api/v1 (routes/api.php → routes/api/v1.php). Sin panel admin.
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Panel del vendedor: todo /seller con la tienda aprobada.
            \Illuminate\Support\Facades\Route::middleware(['web', 'auth', 'shop.approved', 'seller'])
                ->prefix('seller')
                ->name('seller.')
                ->group(base_path('routes/seller.php'));

            // Panel admin: routes/admin.php con su prefijo y, si hay, su dominio.
            \App\Support\AdminRoutes::register();
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        // Antes de StartSession y del CSRF: la lista blanca de IPs del panel
        // (una IP fuera de ella recibe 404 sin sesión ni 419 que delate la
        // ruta) y, en el host admin, la cookie de sesión propia.
        $middleware->web(prepend: [
            \App\Http\Middleware\RestrictAdminIp::class,
            \App\Http\Middleware\IsolateAdminHost::class,
        ]);

        // Un invitado en una ruta admin va al login del panel, no al de la
        // tienda: el panel usa su propio guard y su propia cookie.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->routeIs('admin.*')
            ? route('admin.login')
            : route('login'));

        // El idioma de la API se fija antes que la autenticación, para que un
        // 401 también salga en el idioma de Accept-Language.
        $middleware->prependToPriorityList(
            before: \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            prepend: \App\Http\Middleware\SetApiLocale::class,
        );

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'admin.2fa' => \App\Http\Middleware\EnsureAdminTwoFactor::class,
            'shop.approved' => \App\Http\Middleware\EnsureShopApproved::class,
            'seller' => \App\Http\Middleware\EnsureUserIsSeller::class,
            // spatie/laravel-permission, para los roles del panel (guard 'admin').
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            // API móvil (Sanctum): habilidades del token y cuenta no bloqueada.
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
            'api.active' => \App\Http\Middleware\EnsureApiUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // /api/*: siempre JSON con { message, code } (App\Support\ApiExceptions).
        \App\Support\ApiExceptions::register($exceptions);
    })->create();
