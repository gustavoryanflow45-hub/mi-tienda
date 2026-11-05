<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API para las apps móviles
|--------------------------------------------------------------------------
| Prefijo /api (bootstrap/app.php). Cada versión en su archivo: /api/v1 es
| routes/api/v1.php. Una v2 se añade al lado sin tocar la v1 que ya usan las
| apps publicadas.
|
| Solo compradores y vendedores (tokens de Sanctum de App\Models\User). El
| panel admin no tiene ni tendrá endpoints aquí: si algún día necesita API,
| será otra (/admin-api) con su propio guard. ApiRoutesTest lo comprueba.
*/

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware([\App\Http\Middleware\SetApiLocale::class, 'throttle:api'])
    ->group(base_path('routes/api/v1.php'));
