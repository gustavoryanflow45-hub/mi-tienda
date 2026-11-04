<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\File;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

/**
 * El panel del vendedor vive en routes/seller.php, con sus controladores en
 * App\Http\Controllers\Seller\, y toda acción de vendedor exige la tienda
 * aprobada. La tienda está en Shop\ y el panel admin en Admin\.
 */
class SellerRoutesTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    /** @return Route[] */
    protected function sellerRoutes(): array
    {
        return collect($this->app['router']->getRoutes()->getRoutes())
            ->filter(fn (Route $route) => str_starts_with($route->uri(), 'seller/')
                || str_starts_with((string) $route->getActionName(), 'App\\Http\\Controllers\\Seller\\'))
            ->reject(fn (Route $route) => $route->getName() === 'seller.register' || $route->getName() === 'shops.create')
            ->values()
            ->all();
    }

    public function test_every_seller_route_requires_an_approved_shop_and_the_seller_role(): void
    {
        $routes = $this->sellerRoutes();
        $this->assertNotEmpty($routes);

        foreach ($routes as $route) {
            $middleware = $route->gatherMiddleware();

            foreach (['auth', 'shop.approved', 'seller'] as $required) {
                $this->assertContains($required, $middleware, "{$route->uri()} sin '{$required}'");
            }
            $this->assertStringStartsWith('seller.', (string) $route->getName(), "{$route->uri()} sin nombre seller.*");
            $this->assertStringStartsWith('seller/', $route->uri());
        }
    }

    public function test_the_public_shop_registration_stays_public(): void
    {
        $this->get('/shops/create')->assertOk();
    }

    protected function makePaidOrderFor(User $seller): Order
    {
        $order = $this->makePendingOrder($this->makeCustomer(), $seller);
        $order->markPaid('stripe', 'pi_seller_routes');
        $order->update(['delivery_status' => 'confirmed', 'confirmed_at' => now()]);

        return $order->fresh();
    }

    /**
     * Antes enviar al almacén y publicar vivían fuera de /seller, sin
     * shop.approved: un vendedor con la tienda rechazada podía seguir.
     */
    public function test_a_rejected_shop_can_no_longer_send_to_the_warehouse_or_publish(): void
    {
        $seller = $this->makeSeller();
        $order = $this->makePaidOrderFor($seller);
        $product = $this->makeProduct($seller);
        $product->forceFill(['published' => 0])->save();

        $seller->shop->update(['status' => Shop::REJECTED]);

        $this->actingAs($seller)
            ->post("/seller/orders/{$order->id}/send-to-warehouse")
            ->assertRedirect(route('dashboard'));
        $this->assertSame('confirmed', $order->fresh()->delivery_status);

        $this->actingAs($seller)
            ->postJson("/seller/products/{$product->id}/toggle-published")
            ->assertRedirect(route('dashboard'));
        $this->assertSame(0, (int) $product->fresh()->published);
    }

    public function test_an_approved_seller_still_sends_to_the_warehouse(): void
    {
        $seller = $this->makeSeller();
        $order = $this->makePaidOrderFor($seller);

        $this->actingAs($seller)
            ->post("/seller/orders/{$order->id}/send-to-warehouse")
            ->assertSessionHas('warehouse_success');

        $this->assertSame('warehouse', $order->fresh()->delivery_status);
    }

    public function test_the_old_urls_are_gone(): void
    {
        $seller = $this->makeSeller();
        $order = $this->makePaidOrderFor($seller);

        $this->actingAs($seller)->post("/orders/{$order->id}/send-to-warehouse")->assertNotFound();
    }

    public function test_controllers_are_grouped_by_area(): void
    {
        $root = app_path('Http/Controllers');

        // En la raíz solo queda la base y el idioma, que comparten tienda y panel.
        $this->assertEqualsCanonicalizing(
            ['Controller.php', 'LanguageController.php'],
            array_map('basename', File::files($root)),
        );

        foreach (['Admin', 'Auth', 'Payments', 'Seller', 'Shop'] as $area) {
            foreach (File::files("{$root}/{$area}") as $file) {
                $this->assertStringContainsString(
                    "namespace App\\Http\\Controllers\\{$area};",
                    file_get_contents($file->getPathname()),
                    "{$area}/{$file->getFilename()}"
                );
            }
        }

        $web = file_get_contents(base_path('routes/web.php'));
        $this->assertStringNotContainsString('Controllers\\Admin\\', $web);
        $this->assertStringNotContainsString('Controllers\\Seller\\ProductController', $web);
        $this->assertStringNotContainsString('Controllers\\Seller\\OrderController', $web);
    }
}
