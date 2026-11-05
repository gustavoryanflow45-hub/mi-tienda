<?php

/*
|--------------------------------------------------------------------------
| API para las apps móviles (/api/v1)
|--------------------------------------------------------------------------
|
| Solo compradores y vendedores (App\Models\User, tokens de Sanctum por
| dispositivo). Las cuentas del panel no tienen tokens ni endpoints aquí.
|
| API_TOKEN_TTL_DAYS  Vida de un token. La app lo rota (POST
|                     /api/v1/auth/token/rotate) mientras se usa, así que una
|                     sesión activa no caduca; un dispositivo olvidado sí.
| API_MAX_DEVICES     Dispositivos con sesión abierta a la vez por cuenta.
|                     Al pasarse, el login cierra la sesión más antigua.
*/

return [
    'token_ttl_days' => (int) env('API_TOKEN_TTL_DAYS', 30),

    'max_devices' => (int) env('API_MAX_DEVICES', 10),
];
