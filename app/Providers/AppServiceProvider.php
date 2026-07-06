<?php

namespace App\Providers;

use App\Services\GeolocationService;
use App\Services\Payments\StripeService;
use Illuminate\Support\ServiceProvider;

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
        //
    }
}
