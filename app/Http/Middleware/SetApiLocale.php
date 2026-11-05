<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Idioma de la API: la cabecera Accept-Language de la app ("en", "es-EC"...)
 * entre los de SetLocale::SUPPORTED. La API no tiene sesión, así que la
 * preferencia de la web (session('locale')) no aplica aquí.
 */
class SetApiLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->hasHeader('Accept-Language')
            ? $request->getPreferredLanguage(SetLocale::SUPPORTED)
            : null;

        App::setLocale(in_array($locale, SetLocale::SUPPORTED, true) ? $locale : config('app.locale'));

        $response = $next($request);
        $response->headers->set('Content-Language', App::getLocale());

        return $response;
    }
}
