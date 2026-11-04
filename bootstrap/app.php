<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
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

        // Antes de StartSession: en el host admin cambia la cookie de sesión.
        $middleware->web(prepend: [
            \App\Http\Middleware\IsolateAdminHost::class,
        ]);

        // Un invitado en una ruta admin va al login del panel, no al de la
        // tienda: el panel usa su propio guard y su propia cookie.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->routeIs('admin.*')
            ? route('admin.login')
            : route('login'));

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'admin.2fa' => \App\Http\Middleware\EnsureAdminTwoFactor::class,
            'shop.approved' => \App\Http\Middleware\EnsureShopApproved::class,
            'seller' => \App\Http\Middleware\EnsureUserIsSeller::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
