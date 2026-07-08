# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Multi-vendor e-commerce marketplace built with Laravel 12. Sellers register shops, list products, and receive payments. Customers browse, cart, and checkout. Payment gateway is auto-selected by geolocation: Kushki for Ecuador, Stripe everywhere else.

## Commands

```bash
# First-time setup
composer run setup           # install deps, key:generate, migrate, npm build

# Development (runs all services concurrently)
composer run dev             # artisan serve + queue:listen + pail + npm run dev

# Individual services
php artisan serve            # HTTP server on :8000
php artisan queue:listen     # Background job worker
npm run dev                  # Vite HMR dev mode
npm run build                # Production asset build

# Testing
composer run test            # config:clear then phpunit
php artisan test --filter=OrderTest   # single test class

# Code style
./vendor/bin/pint            # format PHP files
```

## Architecture

### Request Flow

Routes (`routes/web.php`, single file) → Controllers (`app/Http/Controllers/`) → Models/Services → Blade views (`resources/views/`).

No API layer — all responses are server-rendered Blade. AJAX calls (search autocomplete, mini-cart modal, variant prices) return HTML partials or JSON from the same controllers.

`app/Helpers/helpers.php` is autoloaded via composer.json; it defines global helpers like `uploaded_asset($path)` (resolves uploaded file paths, falls back to a placeholder image).

### User Types

`users.user_type` distinguishes three roles: `customer`, `seller`, `admin`. A single `User` model handles all three. Sellers also have a `shops` record. There is no role middleware — seller/admin-only controllers check `in_array($user->user_type, ['seller', 'admin'])` manually (see `SellerProductController`, `SellerOrderController`). Shop registration (`/shops/create`) is public and creates the user with `user_type = 'seller'`.

### Checkout & Payment Flow

This is the most intricate part of the app — read these files together before touching payments: `CheckoutController`, `GeolocationService`, `Payments/StripeController`, `Payments/KushkiController`, `Order::markPaid()`.

1. `GET /checkout` (`CheckoutController@index`) **creates a pending `Order` up front** (status `pendiente`, `payment_status = 'unpaid'`) with its `OrderDetail` rows, and stores the id in `session('checkout_order_id')`. Revisiting checkout reuses that order and syncs totals.
2. Gateway selection: `GeolocationService::gatewayFor()` → `kushki` if country is EC, else `stripe`. Overridable per-request with `/checkout?gateway=stripe|kushki`. **On localhost (127.0.0.1/::1) the country resolves to EC in non-production**, so Kushki is the default gateway in local dev. Country lookups (via `stevebauman/location`) are cached per-IP for 6 hours; the client IP honors the `CF-Connecting-IP` header.
3. Stripe: `POST /payments/stripe/intent` creates a PaymentIntent (via `app/Services/Payments/StripeService.php`); Kushki: `POST /payments/kushki/charge` posts to Kushki's REST API. Kushki's base URL switches on `KUSHKI_ENV` (`test` → `api-uat.kushkipagos.com`, `production` → `api.kushkipagos.com`); default is `test`.
4. Webhooks (`/webhooks/stripe`, `/webhooks/kushki`) mark orders paid — but **webhooks never reach localhost**, so `CheckoutController@success` also verifies the Stripe PaymentIntent directly on the `?payment_intent=` redirect and marks the order paid there. Don't remove either path.
5. `Order::markPaid($gateway, $reference)` is the single place an order becomes paid: it updates payment status, clears the cart, and sends a database `SellerNewOrderNotification` to each seller involved. Seller order lists (`SellerOrderController`) read from these notifications plus `order_details.seller_id`.

Keys live in `config/services.php` (see its header comment for the expected `.env` vars — Stripe and Kushki credentials must match the same environment as `KUSHKI_ENV`). After editing `.env`, run `php artisan config:clear`.

Note: `app/Contracts/PaymentGateway.php` defines an interface but nothing implements it yet; the two payment controllers are independent implementations.

### Product Variants

Products with `variant_product = true` store variant data as JSON columns: `choice_options` (option names/values), `colors`, `variations` (SKU-level price/stock). Flat products use `unit_price` directly. Cart entries store the selected variation string to match the correct variation at checkout.

### Key Models and Relationships

- `Order` → hasMany `OrderDetail` (each carries `seller_id`, denormalized `product_name`/`price`) → belongsTo `Product` / `User` (seller)
- `Product` → belongsTo `Category`, `Brand`, `User` (seller, via `added_by`) → hasMany `ProductStock`, `Review`
- `User` (seller) → hasOne `Shop` → hasMany `Product`
- `Category` is self-referential via `parent_id`

Common query scopes: `scopeActive()`, `scopePublished()`, `scopeFeatured()`, `scopeApproved()`.

Wallet: `WalletRecharge` / `WalletWithdrawal` records with admin approve/reject routes under `/admin/wallet`.

### Delivery / Warehouse Flow

After payment, `orders.delivery_status` advances: `pending` → `confirmed` (seller, `SellerOrderController@confirm`) → `warehouse` (seller, `OrderController@sendToWarehouse`) → `on_the_way` (warehouse panel dispatch) → `delivered`. Each step stamps its timestamp (`confirmed_at`, `warehouse_at`, `dispatched_at` + `dispatched_by`, `delivered_at`) and every transition is guarded by a check on the previous status.

The warehouse panel (`/warehouse`, `WarehouseController`, view `pages/warehouse.blade.php`) is seller/admin-only and drives dispatch (`POST /warehouse/orders/{id}/dispatch`) and delivery (`.../deliver`). Notifications (all `database` channel): `OrderArrivedWarehouseNotification` goes to all admins when a seller sends an order to the warehouse (shown as "nuevas llegadas" in the panel, marked read on view); `OrderStatusUpdatedNotification` goes to the customer on every status change (shown as a banner in `/orders`, marked read on view). The customer-facing tracker with step dates lives in `pages/order-detail.blade.php`. Full flow covered by `tests/Feature/WarehouseFlowTest.php`.

### Frontend

Tailwind CSS v4 via Vite plugin. No Vue/React — plain ES modules in `resources/js/`. Blade layouts in `resources/views/layouts/`, partials heavily used via `@include`.

### Queue & Sessions

Both queue driver and session driver are set to `database` (see `.env`). Run `php artisan queue:listen` during development or queue jobs will not process. Tests override to `sync`/`array` in `phpunit.xml`.

### Tax

Ecuador IVA rate (15%) is read from `config/app.php` (key: `ec_iva_rate`). `KushkiController@charge` back-calculates the IVA base from the order total (Kushki requires `subtotalIva`/`iva` split); an optional `services.kushki.iva_rate` config overrides it.

## Database Notes

- Tests use in-memory SQLite (configured in `phpunit.xml`); local/production uses PostgreSQL (migrated from MySQL 2026-07; the old XAMPP MySQL `woot_db` is kept as a backup and no longer used). PostgreSQL runs as a Windows service independent of XAMPP.
- `database/migrations/` has 17 migration files — always run `php artisan migrate` after pulling changes. `orders`/`order_details` come from `2026_06_10_200000_create_orders_table.php`; database notifications from `2026_06_22_184700_create_notifications_table.php`.
- File uploads go to `storage/app/public/`; the `public/storage` symlink must exist (`php artisan storage:link`).
