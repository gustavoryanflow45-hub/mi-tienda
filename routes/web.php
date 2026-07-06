<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\SellerProductController;
use App\Http\Controllers\SellerOrderController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\Payments\StripeController;
use App\Http\Controllers\Payments\KushkiController;


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
Route::post('/ajax-search', [SearchController::class, 'ajax'])->name('search.ajax');
//Route::post('/search/ajax', [SearchController::class, 'ajax'])->name('search.ajax');


// Categorías
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::post('/category/nav-element-list', [CategoryController::class, 'navElement'])->name('categories.nav-element');
Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('category.show'); // ← nombre unificado

// Productos
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/product/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::post('/product/variant_price', [ProductController::class, 'variantPrice'])->name('product.variant_price');

// Marcas
Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('/brand/{slug}', [BrandController::class, 'show'])->name('brands.show');

// Carrito
Route::get('/cart/mini', [CartController::class, 'mini'])->name('cart.mini');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/addtocart', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/removeFromCart', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/show-cart-modal', [CartController::class, 'modal'])->name('cart.modal');
Route::post('/cart/update-quantity', [CartController::class, 'updateQuantity'])->name('cart.update');

//Chekout
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');

Route::post('/payments/stripe/intent', [StripeController::class, 'createIntent'])->name('payments.stripe.intent');
Route::post('/payments/kushki/charge',  [KushkiController::class, 'charge'])->name('payments.kushki.charge');
Route::post('/webhooks/stripe', [StripeController::class, 'webhook']);
Route::post('/webhooks/kushki', [KushkiController::class, 'webhook']);
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
Route::post('/subscribers', [SubscriberController::class, 'store'])->name('subscribers.store');

// Idioma / Moneda
Route::post('/language', [LanguageController::class, 'change'])->name('language.change');
Route::post('/currency', [CurrencyController::class, 'change'])->name('currency.change');

// Registro de tienda (público — el usuario aún no está logueado)
Route::get('/shops/create', [SellerController::class, 'create'])->name('shops.create');
Route::post('/shops/create', [SellerController::class, 'register'])->name('seller.register');

/*
|--------------------------------------------------------------------------
| Autenticación (solo para invitados)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    Route::get('/password/reset', function () {
        return view('auth.passwords.email');
    })->name('password.request');
    Route::post('/password/email', function () {
        return back()->with('status', 'Si existe una cuenta con ese correo, recibirás el enlace en breve.');
    })->name('password.email');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

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
    Route::post('/orders/{id}/send-to-warehouse', [OrderController::class, 'sendToWarehouse'])->name('orders.send-to-warehouse');

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/add', [WishlistController::class, 'add'])->name('wishlist.add');
    Route::post('/wishlist/remove', [WishlistController::class, 'remove'])->name('wishlist.remove');

    // ── Wallet (usuario) ─────────────────────────────────────────
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/recharge', [WalletController::class, 'recharge'])->name('wallet.recharge');
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw'])->name('wallet.withdraw');

    // ── Wallet (admin) ───────────────────────────────────────────
    Route::get('/admin/wallet', [WalletController::class, 'adminIndex'])->name('admin.wallet');
    Route::post('/admin/wallet/approve/{id}', [WalletController::class, 'approve'])->name('wallet.approve');
    Route::post('/admin/wallet/reject/{id}', [WalletController::class, 'reject'])->name('wallet.reject');
    Route::post('/admin/wallet/withdrawal/approve/{id}', [WalletController::class, 'approveWithdrawal'])->name('wallet.withdrawal.approve');
    Route::post('/admin/wallet/withdrawal/reject/{id}', [WalletController::class, 'rejectWithdrawal'])->name('wallet.withdrawal.reject');

    // ── Seller — productos ───────────────────────────────────────
    // ⚠️ Las rutas específicas (bulk, template, create) van ANTES
    //    que cualquier ruta con parámetro dinámico {id}

    Route::get('/seller/products', [SellerProductController::class, 'index'])->name('seller.products.index');
    Route::get('/seller/products/create', [SellerProductController::class, 'create'])->name('seller.products.create');
    Route::post('/seller/products', [SellerProductController::class, 'store'])->name('seller.products.store');
    Route::get('/seller/products/bulk', [SellerProductController::class, 'bulk'])->name('seller.products.bulk');
    Route::get('/seller/products/bulk/template', [SellerProductController::class, 'bulkTemplate'])->name('seller.products.bulk.template');
    Route::post('/seller/products/bulk', [SellerProductController::class, 'bulkStore'])->name('seller.products.bulk.store');
    Route::post('/seller/products/{id}/toggle-featured', [HomeController::class, 'toggleFeatured'])->name('seller.products.toggle-featured');
    Route::post('/seller/products/{id}/toggle-published', [HomeController::class, 'togglePublished'])->name('seller.products.toggle-published');

    // ── Seller — pedidos ───────────────────────────────────────────
    Route::get('/seller/orders', [SellerOrderController::class, 'index'])->name('seller.orders.index');
    Route::post('/seller/orders/{id}/confirm', [SellerOrderController::class, 'confirm'])->name('seller.orders.confirm');

    // ── Almacén — panel de despacho ───────────────────────────────
    Route::get('/warehouse', [WarehouseController::class, 'index'])->name('warehouse.index');
    Route::post('/warehouse/orders/{id}/dispatch', [WarehouseController::class, 'dispatchOrder'])->name('warehouse.dispatch');
    Route::post('/warehouse/orders/{id}/deliver', [WarehouseController::class, 'deliver'])->name('warehouse.deliver');
});