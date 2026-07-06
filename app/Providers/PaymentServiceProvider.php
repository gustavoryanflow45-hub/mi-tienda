<?php

namespace App\Providers;

use App\Services\Payments\StripeService;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StripeService::class, fn () => new StripeService(
            secret: config('services.stripe.secret'),
        ));
    }
}