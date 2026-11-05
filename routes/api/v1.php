<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DeviceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| /api/v1
|--------------------------------------------------------------------------
| Nombres api.v1.*. Todo responde JSON (bootstrap/app.php → ApiExceptions).
| Documentación: docs/api/v1.md.
*/

// ── Autenticación (sin token) ────────────────────────────────────
// 'throttle:api-auth': 5/min por correo+IP y 20/min por IP.
Route::prefix('auth')->name('auth.')->middleware('throttle:api-auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/email/resend', [AccountController::class, 'resendVerification'])->name('email.resend');
    Route::post('/password/forgot', [AccountController::class, 'forgotPassword'])->name('password.forgot');
});

// ── Con token ────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'api.active'])->group(function () {
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->middleware('abilities:profile:read')->name('me');
        Route::post('/token/rotate', [AuthController::class, 'rotate'])->name('token.rotate');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // Sesiones por dispositivo
        Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
        Route::delete('/devices', [DeviceController::class, 'destroyAll'])->name('devices.destroy-all');
        Route::delete('/devices/{id}', [DeviceController::class, 'destroy'])->whereNumber('id')->name('devices.destroy');
    });
});
