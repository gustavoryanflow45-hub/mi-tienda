<?php

namespace App\Providers;

use App\Services\GeolocationService;
use App\Services\Payments\StripeService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GeolocationService::class);

        $this->app->singleton(StripeService::class, fn() => new StripeService(
            secret: config('services.stripe.secret'),
        ));
    }

    public function boot(): void
    {
        $this->configureAdminRateLimiting();
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
            ->by('user:'.($request->user()?->id ?? $request->ip()))
            ->response($tooMany('code')));
    }
}
