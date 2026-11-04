<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Lista blanca de IPs del panel (alias 'admin.ip', en todo routes/admin.php,
 * login incluido). ADMIN_ALLOWED_IPS acepta IPs y rangos CIDR separados por
 * comas ("203.0.113.7,10.0.0.0/8,2001:db8::/32"); vacío = sin restricción.
 *
 * Fuera de la lista responde 404, no 403: no confirma que ahí hay un panel.
 *
 * La IP es $request->ip(). Detrás de un proxy o de Cloudflare hay que
 * declararlos en trustProxies (bootstrap/app.php) o todas las peticiones
 * llegarán con la IP del proxy. No se lee CF-Connecting-IP a mano: sin
 * Cloudflare delante, cualquiera puede mandar esa cabecera.
 */
class RestrictAdminIp
{
    public function handle(Request $request, Closure $next)
    {
        $allowed = config('admin.allowed_ips');

        if ($allowed && ! IpUtils::checkIp((string) $request->ip(), $allowed)) {
            abort(404);
        }

        return $next($request);
    }
}
