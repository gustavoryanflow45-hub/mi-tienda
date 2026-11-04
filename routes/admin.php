<?php

use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SettlementController;
use App\Http\Controllers\Admin\ShopController;
use App\Http\Controllers\Admin\TwoFactorController;
use App\Http\Controllers\Admin\WalletController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\LanguageController;
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

// Idioma del panel: se guarda en la sesión del panel (otra cookie que la de
// la tienda), así que no sirve el /language de la tienda. Sin 'auth': el
// login también lo ofrece.
Route::post('/language', [LanguageController::class, 'change'])->name('language');

// Guard 'admin' (tabla admins): una sesión de la tienda no sirve aquí.
// 'auth.session' cierra las demás sesiones del admin cuando cambia su
// contraseña (php artisan admin:password).
Route::middleware(['auth:admin', 'auth.session'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // 'admin' va siempre después de 'auth:admin': los invitados acaban en
    // el login, no en un 403.
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

    // Cada ruta exige además su permiso (spatie, guard 'admin'): ver
    // App\Support\AdminPermissions. Sin él, 403.
    Route::middleware(['admin', 'admin.2fa'])->group(function () {
        // Inicio: la primera sección que el rol de la cuenta puede ver.
        Route::get('/', HomeController::class)->name('home');

        // ── Aprobación de tiendas ────────────────────────────────
        Route::get('/shops', [ShopController::class, 'index'])->middleware('permission:shops.view')->name('shops');
        Route::post('/shops/{id}/approve', [ShopController::class, 'approve'])->middleware('permission:shops.decide')->name('shops.approve');
        Route::post('/shops/{id}/reject', [ShopController::class, 'reject'])->middleware('permission:shops.decide')->name('shops.reject');

        // ── Moderación de productos ──────────────────────────────
        // Aprobar/desactivar es products.approved; editar usa el mismo
        // formulario y ProductService que el panel del vendedor.
        Route::get('/products', [ProductController::class, 'index'])->middleware('permission:products.view')->name('products.index');
        Route::get('/products/{id}', [ProductController::class, 'show'])->whereNumber('id')->middleware('permission:products.view')->name('products.show');
        Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->whereNumber('id')->middleware('permission:products.edit')->name('products.edit');
        Route::put('/products/{id}', [ProductController::class, 'update'])->whereNumber('id')->middleware('permission:products.edit')->name('products.update');
        Route::post('/products/{id}/approve', [ProductController::class, 'approve'])->whereNumber('id')->middleware('permission:products.moderate')->name('products.approve');
        Route::post('/products/{id}/disable', [ProductController::class, 'disable'])->whereNumber('id')->middleware('permission:products.moderate')->name('products.disable');

        // ── Pedidos (solo lectura; los mueve el almacén) ─────────
        Route::get('/orders', [OrderController::class, 'index'])->middleware('permission:orders.view')->name('orders.index');
        Route::get('/orders/{id}', [OrderController::class, 'show'])->whereNumber('id')->middleware('permission:orders.view')->name('orders.show');

        // ── Liquidación de ventas a vendedores ───────────────────
        // Liquidar cierra las ventas cobradas del vendedor y deja su
        // panel en cero: solo el admin paga.
        Route::get('/settlements', [SettlementController::class, 'index'])->middleware('permission:settlements.view')->name('settlements');
        Route::post('/settlements/{seller}', [SettlementController::class, 'settle'])->middleware('permission:settlements.settle')->name('settlements.settle');

        // ── Billetera ────────────────────────────────────────────
        // Aprobar una recarga acredita saldo real.
        Route::get('/wallet', [WalletController::class, 'index'])->middleware('permission:wallet.view')->name('wallet');
        Route::post('/wallet/approve/{id}', [WalletController::class, 'approve'])->middleware('permission:wallet.decide')->name('wallet.approve');
        Route::post('/wallet/reject/{id}', [WalletController::class, 'reject'])->middleware('permission:wallet.decide')->name('wallet.reject');
        Route::post('/wallet/withdrawal/approve/{id}', [WalletController::class, 'approveWithdrawal'])->middleware('permission:wallet.decide')->name('wallet.withdrawal.approve');
        Route::post('/wallet/withdrawal/reject/{id}', [WalletController::class, 'rejectWithdrawal'])->middleware('permission:wallet.decide')->name('wallet.withdrawal.reject');

        // ── Almacén — panel de despacho ──────────────────────────
        Route::get('/warehouse', [WarehouseController::class, 'index'])->middleware('permission:warehouse.view')->name('warehouse.index');
        Route::post('/warehouse/orders/{id}/dispatch', [WarehouseController::class, 'dispatchOrder'])->middleware('permission:warehouse.operate')->name('warehouse.dispatch');
        Route::post('/warehouse/orders/{id}/deliver', [WarehouseController::class, 'deliver'])->middleware('permission:warehouse.operate')->name('warehouse.deliver');
    });
});
