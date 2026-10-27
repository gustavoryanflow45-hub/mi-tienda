<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * ¿Llega la petición por el host del panel admin (config('admin.domain'))?
 *
 * Sin ADMIN_DOMAIN no hay host admin: el panel cuelga de /admin en el mismo
 * host que la tienda y esto siempre responde false.
 */
class AdminHost
{
    public static function domain(): ?string
    {
        return config('admin.domain') ?: null;
    }

    public static function matches(Request $request): bool
    {
        $domain = self::domain();

        return $domain !== null && strcasecmp($request->getHost(), $domain) === 0;
    }
}
