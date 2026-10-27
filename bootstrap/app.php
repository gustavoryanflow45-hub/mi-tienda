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
        // tienda: en modo dominio la sesión de la tienda no llega al panel.
        $middleware->redirectGuestsTo(fn (Request $request) => in_array('admin', $request->route()?->gatherMiddleware() ?? [], true)
            ? route('admin.login')
            : route('login'));

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'shop.approved' => \App\Http\Middleware\EnsureShopApproved::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
