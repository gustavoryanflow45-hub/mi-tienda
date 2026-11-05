<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\ProductController;
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

// ── Catálogo (público, sin token) ────────────────────────────────
// Solo productos a la venta (Product::scopeActive), como en la web.
Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/brands', [HomeController::class, 'brands'])->name('brands.index');
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

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
