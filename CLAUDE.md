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

`users.user_type` distinguishes three roles: `customer`, `seller`, `admin`. A single `User` model handles all three. Sellers also have a `shops` record. Shop registration (`/shops/create`) is public and creates the user with `user_type = 'seller'`.

Two middleware aliases are registered in `bootstrap/app.php`:
- **`admin`** (`EnsureUserIsAdmin`) — guards `/admin/wallet` and `/warehouse` as route groups. Always place it after `auth` so guests get redirected to login instead of a 403.
- **`shop.approved`** (`EnsureShopApproved`) — guards every `/seller/*` route. Because `/shops/create` is public and logs the user straight in, `user_type = 'seller'` alone means nothing: the shop must also have `status = 1`. Admins bypass it (they have no shop); pending/rejected/shopless sellers are redirected to `/dashboard` with a `warning` flash.

Admins approve shops at `/admin/shops` (`AdminShopController`, view `admin/shops.blade.php`) — list with status/search filters, ID document thumbnails, and approve/reject actions. `shops.status`: `0` pending, `1` approved, `2` rejected (`Shop::statusLabel()`); the constants live on `AdminShopController`. The dashboard sidebar shows a pending-count badge for admins.

Each decision sends `ShopStatusUpdatedNotification` to the seller on **both** `database` and `mail` (a decision can take days, so the seller may not be logged in). A no-op re-approval sends nothing. `DashboardController` reads the unread ones, marks them read, and passes `$shopUpdates` to both dashboard views, which render `partials/shop-status-banner.blade.php` — same read-once pattern as `OrderStatusUpdatedNotification` in `/orders`. Its `toMail()` calls `route()`, which is safe in console and queue contexts (the console kernel's `SetRequestForConsole` bootstrapper builds a request from `APP_URL`). It only breaks in ad-hoc scripts that boot the **HTTP** kernel without binding a `request` — `url()` fails there too, so swapping helpers is not a fix; bind a request instead.

Controllers additionally check `in_array($user->user_type, ['seller', 'admin'])` in a constructor middleware closure (`SellerProductController`, `SellerOrderController`); `WarehouseController` checks `isAdmin()`. These are defense in depth — the route middleware is the real gate.

`User::$fillable` deliberately **excludes** `user_type`, `balance`, `banned`, `email_verified` and `verification_code`. Assign them explicitly (`$user->user_type = ...`), never through `create()`/`fill()` with request data — see `RegisterController` and `SellerController`. Model factories bypass this via `Model::unguarded()`, so tests can still set them inline.

Flash messages (`success` / `warning` / `error`) render globally via `partials/flash.blade.php`, included in the app layout.

### Email Verification

`users` carries both `email_verified_at` and a legacy `email_verified` flag; `User::isVerified()` accepts either — always use it rather than checking the columns directly.

`VerifyEmailNotification` (mail channel) is sent on customer registration (`RegisterController`) and shop registration (`SellerController`). It builds a **temporary signed URL** to `verification.verify` (`URL::temporarySignedRoute`, 60 min — `VerifyEmailNotification::EXPIRES_MINUTES`), so no token is persisted; the legacy `verification_code` column is unused and merely cleared on success.

Routes (`Auth\VerificationController`): `GET /email/verify` (`verification.notice`, auth) is the "check your inbox" page; `GET /email/verify/{id}/{hash}` (`verification.verify`) is `signed` but deliberately **not** `auth` — the link is opened from an email, often in a browser with no session, and it logs the user in on success; `POST /email/resend` is `auth` + `throttle:6,1`. The `{hash}` is `sha1($user->email)`, so changing the email invalidates outstanding links.

`SellerProductController` bounces unverified sellers to `verification.notice`. `MAIL_MAILER=log` locally — verification emails land in `storage/logs/laravel.log`, not a real inbox.

`app/Http/Middleware/SetLocale.php` runs on every `web` request and applies `session('locale')` (see Localization below).

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

The warehouse panel (`/warehouse`, `WarehouseController`, view `pages/warehouse.blade.php`) is **admin-only** (`admin` middleware on the route group) — it lists every seller's orders together with buyer contact data, so sellers must not reach it; they work from `/seller/orders`. It drives dispatch (`POST /warehouse/orders/{id}/dispatch`) and delivery (`.../deliver`). Notifications (all `database` channel): `OrderArrivedWarehouseNotification` goes to all admins when a seller sends an order to the warehouse (shown as "nuevas llegadas" in the panel, marked read on view); `OrderStatusUpdatedNotification` goes to the customer on every status change (shown as a banner in `/orders`, marked read on view). The customer-facing tracker with step dates lives in `pages/order-detail.blade.php`. Full flow covered by `tests/Feature/WarehouseFlowTest.php`.

### Frontend

Tailwind CSS v4 via Vite plugin. No Vue/React — plain ES modules in `resources/js/`. Blade layouts in `resources/views/layouts/`, partials heavily used via `@include`.

Note: much of the page JS (language/currency switchers, search autocomplete) lives inline in `resources/views/layouts/app.blade.php`, not in `resources/js/` — search both when hunting for a handler.

### Localization

Default locale is `es` (`config/app.php` + `.env`). The topbar switcher POSTs to `/language` (`LanguageController@change`, param name `locale`), which stores `session('locale')`; `SetLocale` middleware applies it on every `web` request. Supported locales are declared once in `SetLocale::SUPPORTED` — the topbar dropdown and the controller both read from it, so adding a language means adding it there plus a `lang/<code>/` directory.

Migration to `__()` is partial: only `partials/topbar` and the two dashboard views use translation keys (`lang/es`, `lang/en`). Every other view still has hardcoded Spanish, so switching to English leaves them untranslated.

### Queue & Sessions

Both queue driver and session driver are set to `database` (see `.env`). Run `php artisan queue:listen` during development or queue jobs will not process. Tests override to `sync`/`array` in `phpunit.xml`.

### Tax

Ecuador IVA rate (15%) is read from `config/app.php` (key: `ec_iva_rate`). `KushkiController@charge` back-calculates the IVA base from the order total (Kushki requires `subtotalIva`/`iva` split); an optional `services.kushki.iva_rate` config overrides it.

## Database Notes

- Tests use in-memory SQLite (configured in `phpunit.xml`); local/production uses PostgreSQL (migrated from MySQL 2026-07; the old XAMPP MySQL `woot_db` is kept as a backup and no longer used). PostgreSQL runs as a Windows service independent of XAMPP.
- `database/migrations/` has 17 migration files — always run `php artisan migrate` after pulling changes. `orders`/`order_details` come from `2026_06_10_200000_create_orders_table.php`; database notifications from `2026_06_22_184700_create_notifications_table.php`.
- File uploads go to `storage/app/public/`; the `public/storage` symlink must exist (`php artisan storage:link`).
