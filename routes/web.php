<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Payments\PayPhoneController;
use App\Http\Controllers\Payments\StripeController;
use App\Http\Controllers\Seller\RegistrationController;
use App\Http\Controllers\Shop\BrandController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CategoryController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\CompareController;
use App\Http\Controllers\Shop\CurrencyController;
use App\Http\Controllers\Shop\DashboardController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\MyProductsController;
use App\Http\Controllers\Shop\OrderController;
use App\Http\Controllers\Shop\PageController;
use App\Http\Controllers\Shop\ProductController;
use App\Http\Controllers\Shop\ProfileController;
use App\Http\Controllers\Shop\SearchController;
use App\Http\Controllers\Shop\SubscriberController;
use App\Http\Controllers\Shop\WalletController;
use App\Http\Controllers\Shop\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Públicas
|--------------------------------------------------------------------------
*/

// Inicio
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/home/section/{section}', [HomeController::class, 'section'])->name('home.section');

// Búsqueda
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::post('/ajax-search', [SearchController::class, 'ajax'])->middleware('throttle:search')->name('search.ajax');
// Route::post('/search/ajax', [SearchController::class, 'ajax'])->name('search.ajax');

// Categorías
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::post('/category/nav-element-list', [CategoryController::class, 'navElement'])->name('categories.nav-element');
Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('category.show'); // ← nombre unificado

// Productos
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/product/{slug}', [ProductController::class, 'show'])->name('products.show');

// Marcas
Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('/brand/{slug}', [BrandController::class, 'show'])->name('brands.show');

// Carrito — límites en AppServiceProvider::configureStorefrontRateLimiting()
Route::middleware('throttle:cart')->group(function () {
    Route::get('/cart/mini', [CartController::class, 'mini'])->name('cart.mini');
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/addtocart', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/removeFromCart', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/cart/show-cart-modal', [CartController::class, 'modal'])->name('cart.modal');
    Route::post('/cart/update-quantity', [CartController::class, 'updateQuantity'])->name('cart.update');
});

// Chekout
// Cada visita crea o sincroniza el pedido pendiente: throttle:checkout.
Route::middleware('throttle:checkout')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout/shipping', [CheckoutController::class, 'saveShipping'])->name('checkout.shipping');
});

// El pedido se crea aquí, al iniciar el cobro, a partir del carrito del
// usuario autenticado: por eso ambos exigen sesión. 'throttle:payments'
// (AppServiceProvider) frena las pruebas de tarjetas robadas en masa.
Route::post('/payments/stripe/intent', [StripeController::class, 'createIntent'])->middleware(['auth', 'throttle:payments'])->name('payments.stripe.intent');
Route::post('/payments/payphone/prepare', [PayPhoneController::class, 'prepare'])->middleware(['auth', 'throttle:payments'])->name('payments.payphone.prepare');
// PayPhone vuelve aquí tras el pago: hay que confirmar en 5 min o revierte
// el cobro, así que no exige sesión ni lleva límite (ver PayPhoneController).
Route::get('/payments/payphone/return', [PayPhoneController::class, 'handleReturn'])->name('payments.payphone.return');
Route::get('/payments/payphone/cancel', [PayPhoneController::class, 'cancel'])->name('payments.payphone.cancel');
Route::post('/webhooks/stripe', [StripeController::class, 'webhook']);
Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');

// Comparar
Route::post('/compare/addToCompare', [CompareController::class, 'add'])->name('compare.add');

// Páginas estáticas
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/return-policy', [PageController::class, 'returnPolicy'])->name('return-policy');
Route::get('/support-policy', [PageController::class, 'supportPolicy'])->name('support-policy');
Route::get('/privacy-policy', [PageController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('/track-your-order', [PageController::class, 'trackOrder'])->name('track-order');
Route::get('/affiliate', [PageController::class, 'affiliate'])->name('affiliate');

// Newsletter
Route::post('/subscribers', [SubscriberController::class, 'store'])->middleware('throttle:subscribe')->name('subscribers.store');

// Idioma / Moneda
Route::post('/language', [LanguageController::class, 'change'])->name('language.change');
Route::post('/currency', [CurrencyController::class, 'change'])->name('currency.change');

// Registro de tienda (público — el usuario aún no está logueado)
Route::get('/shops/create', [RegistrationController::class, 'create'])->name('shops.create');
Route::post('/shops/create', [RegistrationController::class, 'register'])->middleware('throttle:shop-register')->name('seller.register');

/*
|--------------------------------------------------------------------------
| Autenticación (solo para invitados)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:register');
    // Recuperación de contraseña (broker de Laravel, token en password_reset_tokens).
    Route::get('/password/reset', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/password/email', [PasswordResetController::class, 'email'])
        ->middleware('throttle:6,1')->name('password.email');
    Route::get('/password/reset/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/password/reset', [PasswordResetController::class, 'update'])
        ->middleware('throttle:6,1')->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Verificación de correo
|--------------------------------------------------------------------------
| El enlace de 'verify' va firmado y caduca, así que no necesita sesión:
| se abre desde el correo, muchas veces en otro navegador.
*/
Route::get('/email/verify', [VerificationController::class, 'notice'])
    ->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
    ->middleware('signed')->name('verification.verify');

Route::post('/email/resend', [VerificationController::class, 'resend'])
    ->middleware(['auth', 'throttle:6,1'])->name('verification.resend');

/*
|--------------------------------------------------------------------------
| Rutas Protegidas (requieren autenticación)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Perfil
    Route::get('/profile', function () {
        return view('users.profile');
    })->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Pedidos
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');

    // Mis productos: lo que el usuario fue agregando al carrito
    Route::get('/my-products', [MyProductsController::class, 'index'])->name('my-products.index');

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/add', [WishlistController::class, 'add'])->name('wishlist.add');
    Route::post('/wishlist/remove', [WishlistController::class, 'remove'])->name('wishlist.remove');

    // ── Wallet (usuario) ─────────────────────────────────────────
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/recharge', [WalletController::class, 'recharge'])->name('wallet.recharge');
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw'])->name('wallet.withdraw');
});

// El panel del vendedor (/seller/*) está en routes/seller.php.
