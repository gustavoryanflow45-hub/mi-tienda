<?php

use App\Http\Controllers\Seller\OrderController;
use App\Http\Controllers\Seller\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Panel del vendedor
|--------------------------------------------------------------------------
| Lo registra bootstrap/app.php dentro de 'web' + 'auth' + 'shop.approved'
| + 'seller', con prefijo /seller y nombre 'seller.'.
|
| Nadie vende hasta que un admin apruebe su tienda: /shops/create es público
| y auto-loguea, así que el rol 'seller' por sí solo no basta. Por eso TODA
| acción de vendedor va aquí, incluidas las que antes vivían fuera de
| /seller (publicar/despublicar y enviar al almacén), que no pasaban por
| shop.approved.
|
| El registro de la tienda (/shops/create) sigue en routes/web.php: es
| público. Nada de aquí pertenece al panel admin (routes/admin.php).
*/

// ── Productos ────────────────────────────────────────────────────
// ⚠️ Las rutas específicas (bulk, template, create) van ANTES que
//    cualquier ruta con parámetro dinámico {id}.
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
Route::post('/products', [ProductController::class, 'store'])->name('products.store');
Route::get('/products/bulk', [ProductController::class, 'bulk'])->name('products.bulk');
Route::get('/products/bulk/template', [ProductController::class, 'bulkTemplate'])->name('products.bulk.template');
Route::post('/products/bulk', [ProductController::class, 'bulkStore'])->name('products.bulk.store');
Route::post('/products/{id}/toggle-published', [ProductController::class, 'togglePublished'])->name('products.toggle-published');
Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
Route::put('/products/{id}', [ProductController::class, 'update'])->name('products.update');

// ── Pedidos ──────────────────────────────────────────────────────
Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
Route::post('/orders/{id}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
Route::post('/orders/{id}/send-to-warehouse', [OrderController::class, 'sendToWarehouse'])->name('orders.send-to-warehouse');
