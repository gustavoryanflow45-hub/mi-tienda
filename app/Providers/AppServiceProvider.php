<?php

namespace App\Providers;

use App\Services\GeolocationService;
use App\Services\Payments\PayPhoneService;
use App\Services\Payments\StripeService;
use App\Support\ApiExceptions;
use App\View\Composers\AdminNavComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GeolocationService::class);

        $this->app->singleton(StripeService::class, fn () => new StripeService(
            secret: config('services.stripe.secret'),
        ));

        $this->app->singleton(PayPhoneService::class, fn () => new PayPhoneService(
            token: config('services.payphone.token'),
            storeId: config('services.payphone.store_id'),
        ));
    }

    public function boot(): void
    {
        $this->configureAdminRateLimiting();
        $this->configurePaymentRateLimiting();
        $this->configureStorefrontRateLimiting();
        $this->configureAccountRateLimiting();
        $this->configureApiRateLimiting();

        // Barra del panel (layouts/admin): admin, 2FA y contadores.
        View::composer('layouts.admin', AdminNavComposer::class);
    }

    /**
     * Formularios públicos de la tienda: login, registro, alta de tienda y
     * boletín. Son formularios clásicos, así que al pasarse vuelven atrás
     * con lo escrito (sin contraseñas ni archivos) y el aviso como error del
     * campo de correo; el boletín vive en el pie, que no pinta errores, y usa
     * el flash 'error' global. Los límites por hora frenan las altas masivas
     * sin estorbar a varias personas detrás de la misma IP.
     */
    /**
     * API móvil. 'api' va en todo /api/v1: 60/min por usuario con token o
     * por IP sin él. 'api-auth' en login, alta, reenvío de verificación y
     * contraseña olvidada: la misma forma que 'login' de la web (por
     * correo+IP y por IP), para que no sirvan de diccionario. El 429 lo pinta
     * App\Support\ApiExceptions (code too_many_requests, con Retry-After).
     */
    protected function configureApiRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user() ? 'user:'.$request->user()->id : 'ip:'.$request->ip()));

        // Solicitar recargas y retiros: 10/min por usuario (suben comprobantes
        // y descuentan saldo); de sobra para un uso real.
        RateLimiter::for('api-wallet', fn (Request $request) => Limit::perMinute(10)
            ->by('user:'.($request->user()?->id ?? $request->ip())));

        RateLimiter::for('api-auth', fn (Request $request) => [
            Limit::perMinute(5)->by('email:'.Str::lower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by('ip:'.$request->ip()),
        ]);
    }

    /**
     * Los límites 'cart', 'checkout' y 'payments' los comparten la web y la
     * API (mismo contador: la app no duplica el cupo). Bajo /api el 429 lleva
     * el formato de la API (code too_many_requests).
     */
    protected static function apiTooMany(array $headers): JsonResponse
    {
        return ApiExceptions::render(new ThrottleRequestsException('', null, $headers));
    }

    protected function configureAccountRateLimiting(): void
    {
        $tooMany = fn (?string $field) => function (Request $request, array $headers) use ($field) {
            $message = __('Demasiados intentos. Espera :seconds segundos y vuelve a probar.', [
                'seconds' => $headers['Retry-After'] ?? 60,
            ]);

            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $message], 429, $headers);
            }

            $back = back()->withInput($request->except(['password', 'password_confirmation']));

            return $field ? $back->withErrors([$field => $message]) : $back->with('error', $message);
        };

        // Igual que el login del panel: adivinar la contraseña de una cuenta
        // y probar muchos correos desde la misma máquina.
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)
                ->by('email:'.Str::lower((string) $request->input('email')).'|'.$request->ip())
                ->response($tooMany('email')),
            Limit::perMinute(20)->by('ip:'.$request->ip())->response($tooMany('email')),
        ]);

        RateLimiter::for('register', fn (Request $request) => [
            Limit::perMinute(5)->by('min:'.$request->ip())->response($tooMany('email')),
            Limit::perHour(20)->by('hour:'.$request->ip())->response($tooMany('email')),
        ]);

        // Crea un usuario y sube documentos de identidad en cada envío.
        RateLimiter::for('shop-register', fn (Request $request) => [
            Limit::perMinute(3)->by('min:'.$request->ip())->response($tooMany('email')),
            Limit::perHour(10)->by('hour:'.$request->ip())->response($tooMany('email')),
        ]);

        RateLimiter::for('subscribe', fn (Request $request) => [
            Limit::perMinute(5)->by('min:'.$request->ip())->response($tooMany(null)),
            Limit::perHour(30)->by('hour:'.$request->ip())->response($tooMany(null)),
        ]);
    }

    /**
     * Búsqueda (pública, LIKE sobre products), carrito y checkout (escriben
     * en la BD en cada llamada). Los límites dejan de sobra el uso normal:
     * el autocompletado ya espera 250 ms entre pulsaciones y el mini-carrito
     * se recarga en cada página.
     *
     * Casi todo lo llaman por AJAX (jQuery o fetch sin cabeceras AJAX), así
     * que la respuesta es JSON con 'status' y 'message', salvo las dos
     * páginas completas, /cart y /checkout, que muestran errors/429.
     */
    protected function configureStorefrontRateLimiting(): void
    {
        $tooMany = function (Request $request, array $headers) {
            if ($request->is('api/*')) {
                return self::apiTooMany($headers);
            }

            $message = __('Demasiadas peticiones. Espera :seconds segundos y vuelve a probar.', [
                'seconds' => $headers['Retry-After'] ?? 60,
            ]);

            if ($request->isMethod('GET') && $request->routeIs('cart.index', 'checkout')) {
                return response()->view('errors.429', ['message' => $message], 429, $headers);
            }

            return response()->json(['status' => 'error', 'message' => $message], 429, $headers);
        };

        $byUser = fn (Request $request) => 'user:'.($request->user()?->id ?? $request->ip());

        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(60)
            ->by('ip:'.$request->ip())->response($tooMany));

        RateLimiter::for('cart', fn (Request $request) => Limit::perMinute(120)
            ->by($byUser($request))->response($tooMany));

        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(30)
            ->by($byUser($request))->response($tooMany));
    }

    /**
     * Iniciar un cobro (PaymentIntent de Stripe, pago de PayPhone) llama a la
     * pasarela: sin límite, esas rutas sirven para probar tarjetas robadas en
     * masa. 10 por minuto por usuario de sobra para quien reintenta un pago,
     * y 30 por IP para quien rota cuentas desde la misma máquina. Responde
     * JSON con 'message' (lo que muestra el checkout) y 'status'. El webhook
     * de Stripe y la vuelta de PayPhone no llevan límite: confirmar un pago
     * ya hecho no debe bloquearse nunca.
     */
    protected function configurePaymentRateLimiting(): void
    {
        $tooMany = fn (Request $request, array $headers) => $request->is('api/*')
            ? self::apiTooMany($headers)
            : response()->json([
                'status' => 'error',
                'message' => __('Demasiados intentos de pago. Espera :seconds segundos y vuelve a probar.', [
                    'seconds' => $headers['Retry-After'] ?? 60,
                ]),
            ], 429, $headers);

        RateLimiter::for('payments', fn (Request $request) => [
            Limit::perMinute(10)->by('user:'.($request->user()?->id ?? $request->ip()))->response($tooMany),
            Limit::perMinute(30)->by('ip:'.$request->ip())->response($tooMany),
        ]);
    }

    /**
     * Límites del panel admin (ver routes/web.php). Al pasarse, el formulario
     * vuelve con un error en vez de una página 429 en blanco.
     */
    protected function configureAdminRateLimiting(): void
    {
        $tooMany = fn (string $field) => fn (Request $request, array $headers) => back()
            ->withInput($request->only('email'))
            ->withErrors([$field => __('Demasiados intentos. Espera :seconds segundos y vuelve a probar.', [
                'seconds' => $headers['Retry-After'] ?? 60,
            ])]);

        // 5 por minuto por correo+IP (adivinar la contraseña de un admin) y
        // 20 por IP (probar muchos correos desde la misma máquina).
        RateLimiter::for('admin-login', fn (Request $request) => [
            Limit::perMinute(5)
                ->by('email:'.Str::lower((string) $request->input('email')).'|'.$request->ip())
                ->response($tooMany('email')),
            Limit::perMinute(20)->by('ip:'.$request->ip())->response($tooMany('email')),
        ]);

        // 5 por minuto por admin: un código de 6 dígitos no aguanta fuerza
        // bruta sin límite. El bloqueo largo (10 fallos en 15 min cierran la
        // sesión) está en TwoFactorController.
        RateLimiter::for('admin-2fa', fn (Request $request) => Limit::perMinute(5)
            ->by('admin:'.($request->user('admin')?->id ?? $request->ip()))
            ->response($tooMany('code')));
    }
}
