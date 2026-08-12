<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    /** Idiomas que la tienda sabe servir. */
    public const SUPPORTED = ['es', 'en'];

    /**
     * Aplica en cada request el idioma guardado por LanguageController.
     * Sin esto la sesión se escribe pero __() sigue usando config('app.locale').
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->session()->get('locale');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
