<?php

use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\SettlementController;
use App\Http\Controllers\Admin\ShopController;
use App\Http\Controllers\Admin\TwoFactorController;
use App\Http\Controllers\Admin\WalletController;
use App\Http\Controllers\Admin\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Panel de administración
|--------------------------------------------------------------------------
| Lo registra App\Support\AdminRoutes (llamado desde bootstrap/app.php)
| dentro del grupo 'web', con nombre 'admin.' y prefijo ADMIN_PATH (por
| defecto /admin). Con ADMIN_DOMAIN las rutas solo existen en ese host y en
| la tienda dan 404. Ver config/admin.php.
|
| Aquí no va nada de la tienda ni del panel de vendedores, y la futura API
| móvil (routes/api.php) no debe exponer ninguna de estas rutas.
|
| El almacén va aquí porque lista los pedidos de TODOS los vendedores junto
| con los datos de contacto del comprador: los vendedores gestionan lo suyo
| desde /seller/orders.
*/

// Login propio: en modo dominio la cookie de la tienda no llega aquí.
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])
    ->middleware('throttle:admin-login')->name('login.attempt');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // 'admin' va siempre después de 'auth': los invitados acaban en el
    // login, no en un 403.
    Route::middleware('admin')->group(function () {
        // ── Segundo factor ───────────────────────────────────────
        // Después del login y antes de cualquier otra ruta del panel.
        // El alta solo con el enlace firmado de `admin:2fa-invite`.
        Route::get('/two-factor', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
        Route::post('/two-factor', [TwoFactorController::class, 'verify'])
            ->middleware('throttle:admin-2fa')->name('two-factor.verify');
        Route::get('/two-factor/setup', [TwoFactorController::class, 'setup'])->middleware('signed')->name('two-factor.setup');
        Route::post('/two-factor/setup', [TwoFactorController::class, 'confirm'])
            ->middleware(['signed', 'throttle:admin-2fa'])->name('two-factor.confirm');
    });

    Route::middleware(['admin', 'admin.2fa'])->group(function () {
        Route::get('/', fn () => redirect()->route('admin.shops'))->name('home');

        // ── Aprobación de tiendas ────────────────────────────────
        Route::get('/shops', [ShopController::class, 'index'])->name('shops');
        Route::post('/shops/{id}/approve', [ShopController::class, 'approve'])->name('shops.approve');
        Route::post('/shops/{id}/reject', [ShopController::class, 'reject'])->name('shops.reject');

        // ── Liquidación de ventas a vendedores ───────────────────
        // Liquidar cierra las ventas cobradas del vendedor y deja su
        // panel en cero: solo el admin paga.
        Route::get('/settlements', [SettlementController::class, 'index'])->name('settlements');
        Route::post('/settlements/{seller}', [SettlementController::class, 'settle'])->name('settlements.settle');

        // ── Billetera ────────────────────────────────────────────
        // Aprobar una recarga acredita saldo real.
        Route::get('/wallet', [WalletController::class, 'index'])->name('wallet');
        Route::post('/wallet/approve/{id}', [WalletController::class, 'approve'])->name('wallet.approve');
        Route::post('/wallet/reject/{id}', [WalletController::class, 'reject'])->name('wallet.reject');
        Route::post('/wallet/withdrawal/approve/{id}', [WalletController::class, 'approveWithdrawal'])->name('wallet.withdrawal.approve');
        Route::post('/wallet/withdrawal/reject/{id}', [WalletController::class, 'rejectWithdrawal'])->name('wallet.withdrawal.reject');

        // ── Almacén — panel de despacho ──────────────────────────
        Route::get('/warehouse', [WarehouseController::class, 'index'])->name('warehouse.index');
        Route::post('/warehouse/orders/{id}/dispatch', [WarehouseController::class, 'dispatchOrder'])->name('warehouse.dispatch');
        Route::post('/warehouse/orders/{id}/deliver', [WarehouseController::class, 'deliver'])->name('warehouse.deliver');
    });
});
