<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

/**
 * Límites de búsqueda, carrito y checkout (AppServiceProvider::
 * configureStorefrontRateLimiting). Las llamadas AJAX reciben JSON con
 * 'status' y 'message'; /cart y /checkout, la página errors/429.
 */
class StorefrontThrottleTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    public function test_ajax_search_is_limited_to_sixty_per_minute_per_ip(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->post('/ajax-search', ['search' => 'zapa'])->assertOk();
        }

        $this->post('/ajax-search', ['search' => 'zapa'])
            ->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertJsonPath('status', 'error')
            ->assertJsonStructure(['message']);

        // Otra IP no hereda el límite.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->post('/ajax-search', ['search' => 'zapa'])
            ->assertOk();
    }

    public function test_cart_calls_are_limited_per_user_and_the_page_shows_the_429_screen(): void
    {
        $customer = $this->makeCustomer();

        for ($i = 0; $i < 120; $i++) {
            $this->actingAs($customer)->get('/cart/mini')->assertOk();
        }

        $this->actingAs($customer)->get('/cart/mini')
            ->assertStatus(429)
            ->assertJsonPath('status', 'error');

        $this->actingAs($customer)->post('/cart/update-quantity', ['cart_id' => 1, 'quantity' => 2])
            ->assertStatus(429)
            ->assertJsonStructure(['status', 'message']);

        $this->actingAs($customer)->get('/cart')
            ->assertStatus(429)
            ->assertSee(__('Demasiadas peticiones'));

        $this->actingAs($this->makeCustomer())->get('/cart/mini')->assertOk();
    }

    public function test_checkout_is_limited_to_thirty_per_minute_per_user(): void
    {
        $customer = $this->makeCustomer();

        for ($i = 0; $i < 30; $i++) {
            $this->assertNotSame(429, $this->actingAs($customer)->get('/checkout')->status());
        }

        $this->actingAs($customer)->get('/checkout')
            ->assertStatus(429)
            ->assertSee(__('Demasiadas peticiones'));

        $this->actingAs($customer)->postJson('/checkout/shipping', [])
            ->assertStatus(429)
            ->assertJsonPath('status', 'error');

        $this->travel(61)->seconds();
        $this->assertNotSame(429, $this->actingAs($customer)->get('/checkout')->status());
    }

    public function test_payment_confirmation_and_catalog_pages_are_not_throttled(): void
    {
        foreach (['checkout.success', 'search', 'products.index'] as $name) {
            $route = $this->app['router']->getRoutes()->getByName($name);

            $this->assertEmpty(
                array_filter($route->gatherMiddleware(), fn ($m) => str_starts_with($m, 'throttle')),
                "{$name} no debería llevar límite",
            );
        }
    }
}
