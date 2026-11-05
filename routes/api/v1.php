<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\Seller;
use App\Http\Controllers\Api\V1\WalletController;
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

        // Token push (FCM) de este dispositivo; el envío aún no existe.
        Route::put('/push-token', [DeviceController::class, 'registerPushToken'])->name('push-token.update');
        Route::delete('/push-token', [DeviceController::class, 'unregisterPushToken'])->name('push-token.destroy');
    });

    // ── Carrito ──────────────────────────────────────────────────
    // El mismo de la web (tabla carts) y las mismas reglas (CartService).
    // 'throttle:cart' comparte contador con el carrito web.
    Route::prefix('cart')->name('cart.')->middleware(['abilities:cart:write', 'throttle:cart'])->group(function () {
        Route::get('/', [CartController::class, 'show'])->name('show');
        Route::delete('/', [CartController::class, 'clear'])->name('clear');
        Route::post('/items', [CartController::class, 'store'])->name('items.store');
        Route::patch('/items/{item}', [CartController::class, 'update'])->whereNumber('item')->name('items.update');
        Route::delete('/items/{item}', [CartController::class, 'destroy'])->whereNumber('item')->name('items.destroy');
    });

    // ── Checkout y pagos ─────────────────────────────────────────
    // Crear el pedido lleva 'throttle:checkout' e iniciar un cobro
    // 'throttle:payments', con los mismos contadores que la web (frenan el
    // uso de la API para probar tarjetas robadas). Confirmar no se limita:
    // registrar un pago ya hecho no debe bloquearse nunca.
    Route::prefix('checkout')->name('checkout.')->middleware('abilities:checkout:write')->group(function () {
        Route::get('/', [CheckoutController::class, 'show'])->name('show');
        Route::post('/orders', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('orders.store');

        Route::prefix('orders/{order}')->whereNumber('order')->name('orders.')->group(function () {
            Route::get('/', [CheckoutController::class, 'order'])->name('show');
            Route::post('/stripe', [PaymentController::class, 'stripe'])->middleware('throttle:payments')->name('stripe');
            Route::post('/stripe/confirm', [PaymentController::class, 'stripeConfirm'])->name('stripe.confirm');
            Route::post('/payphone', [PaymentController::class, 'payphone'])->middleware('throttle:payments')->name('payphone');
            Route::post('/payphone/confirm', [PaymentController::class, 'payphoneConfirm'])->name('payphone.confirm');
        });
    });

    // ── Perfil ───────────────────────────────────────────────────
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->middleware('abilities:profile:read')->name('show');
        Route::middleware('abilities:profile:write')->group(function () {
            Route::patch('/', [ProfileController::class, 'update'])->name('update');
            Route::post('/avatar', [ProfileController::class, 'avatar'])->name('avatar');
            Route::put('/password', [ProfileController::class, 'password'])->middleware('throttle:api-auth')->name('password');
        });
    });

    // ── Pedidos del comprador ────────────────────────────────────
    Route::prefix('orders')->name('orders.')->middleware('abilities:orders:read')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{order}', [OrderController::class, 'show'])->whereNumber('order')->name('show');
    });

    // ── Billetera ────────────────────────────────────────────────
    // Solicitar recargas y retiros lleva 'throttle:api-wallet' (mueve dinero
    // y sube comprobantes) y 'api.idempotent' (cabecera Idempotency-Key: un
    // reintento o un doble toque no crea dos solicitudes). Aprobar o
    // rechazar es solo del panel: no hay rutas para eso aquí.
    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::middleware('abilities:wallet:read')->group(function () {
            Route::get('/', [WalletController::class, 'show'])->name('show');
            Route::get('/movements', [WalletController::class, 'movements'])->name('movements.index');
            Route::get('/recharges', [WalletController::class, 'recharges'])->name('recharges.index');
            Route::get('/recharges/{recharge}', [WalletController::class, 'showRecharge'])->whereNumber('recharge')->name('recharges.show');
            Route::get('/withdrawals', [WalletController::class, 'withdrawals'])->name('withdrawals.index');
            Route::get('/withdrawals/{withdrawal}', [WalletController::class, 'showWithdrawal'])->whereNumber('withdrawal')->name('withdrawals.show');
            Route::get('/settlements', [WalletController::class, 'settlements'])->name('settlements.index');
        });

        Route::middleware(['abilities:wallet:write', 'throttle:api-wallet', 'api.idempotent'])->group(function () {
            Route::post('/recharges', [WalletController::class, 'recharge'])->name('recharges.store');
            Route::post('/withdrawals', [WalletController::class, 'withdraw'])->name('withdrawals.store');
        });
    });

    // ── Notificaciones ───────────────────────────────────────────
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::middleware('abilities:profile:read')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
        });

        Route::middleware('abilities:profile:write')->group(function () {
            Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
            Route::post('/{notification}/read', [NotificationController::class, 'markRead'])->whereUuid('notification')->name('read');
        });
    });

    // ── Vendedor ─────────────────────────────────────────────────
    // Tras la habilidad, 'api.seller' vuelve a comprobar en cada petición que
    // la tienda siga aprobada: seller:* solo se recalcula al rotar el token.
    Route::prefix('seller')->name('seller.')->group(function () {
        Route::middleware(['abilities:seller:products', 'api.seller'])->prefix('products')->name('products.')->group(function () {
            Route::get('/options', [Seller\ProductController::class, 'options'])->name('options');
            Route::get('/', [Seller\ProductController::class, 'index'])->name('index');
            Route::post('/', [Seller\ProductController::class, 'store'])->name('store');
            Route::get('/{product}', [Seller\ProductController::class, 'show'])->whereNumber('product')->name('show');
            Route::put('/{product}', [Seller\ProductController::class, 'update'])->whereNumber('product')->name('update');
            Route::post('/{product}/toggle-published', [Seller\ProductController::class, 'togglePublished'])->whereNumber('product')->name('toggle-published');
        });

        Route::middleware(['abilities:seller:orders', 'api.seller'])->group(function () {
            Route::get('/dashboard', Seller\DashboardController::class)->name('dashboard');
            Route::get('/orders', [Seller\OrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/{order}', [Seller\OrderController::class, 'show'])->whereNumber('order')->name('orders.show');
            Route::post('/orders/{order}/confirm', [Seller\OrderController::class, 'confirm'])->whereNumber('order')->name('orders.confirm');
            Route::post('/orders/{order}/send-to-warehouse', [Seller\OrderController::class, 'sendToWarehouse'])->whereNumber('order')->name('orders.send-to-warehouse');
        });
    });
});
