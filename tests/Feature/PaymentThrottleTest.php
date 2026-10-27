<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

/**
 * Iniciar un cobro llama a la pasarela: 'throttle:payments' impide usar estas
 * rutas para probar tarjetas en masa. Con el carrito vacío los controladores
 * responden 422 sin tocar la red, y el límite cuenta igual cada intento.
 */
class PaymentThrottleTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    public static function paymentRoutes(): array
    {
        return [
            'stripe' => ['/payments/stripe/intent'],
            'kushki' => ['/payments/kushki/charge'],
        ];
    }

    #[DataProvider('paymentRoutes')]
    public function test_payment_start_is_limited_to_ten_per_minute_per_user(string $uri): void
    {
        $customer = $this->makeCustomer();

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($customer)->postJson($uri)->assertStatus(422);
        }

        $this->actingAs($customer)->postJson($uri)
            ->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertJsonPath('status', 'error')
            ->assertJsonStructure(['message']);

        // Otro usuario no hereda el límite.
        $this->actingAs($this->makeCustomer())->postJson($uri)->assertStatus(422);

        // Pasado el minuto se puede volver a intentar.
        $this->travel(61)->seconds();
        $this->actingAs($customer)->postJson($uri)->assertStatus(422);
    }

    public function test_both_gateways_share_the_same_budget(): void
    {
        $customer = $this->makeCustomer();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($customer)->postJson('/payments/stripe/intent');
            $this->actingAs($customer)->postJson('/payments/kushki/charge');
        }

        $this->actingAs($customer)->postJson('/payments/stripe/intent')->assertStatus(429);
    }

    public function test_webhooks_are_not_throttled(): void
    {
        foreach (['/webhooks/stripe', '/webhooks/kushki'] as $uri) {
            $route = $this->app['router']->getRoutes()->match(request()->create($uri, 'POST'));

            $this->assertEmpty(
                array_filter($route->gatherMiddleware(), fn ($m) => str_starts_with($m, 'throttle')),
                "{$uri} no debe llevar límite: la llama la pasarela",
            );
        }
    }
}
