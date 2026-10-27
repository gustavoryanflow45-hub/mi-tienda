<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

/**
 * Un vendedor solo ve y toca lo suyo, aunque conozca el id del producto o
 * del pedido de otro (ProductPolicy, OrderPolicy, OrderDetailPolicy,
 * ShopPolicy).
 */
class OwnershipPolicyTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    protected function makePaidOrder(User $customer, User $seller): Order
    {
        $order = $this->makePendingOrder($customer, $seller);
        $order->markPaid('stripe', 'pi_ownership_test');

        return $order->fresh();
    }

    // ── Productos ────────────────────────────────────────────────

    public function test_seller_cannot_edit_another_sellers_product(): void
    {
        $owner = $this->makeSeller();
        $intruder = $this->makeSeller();
        $product = $this->makeProduct($owner);

        $this->actingAs($intruder)->get("/seller/products/{$product->id}/edit")->assertForbidden();

        $this->actingAs($intruder)
            ->put("/seller/products/{$product->id}", ['name' => 'Robado'])
            ->assertForbidden();

        $this->assertNotSame('Robado', $product->fresh()->name);
    }

    public function test_seller_cannot_toggle_another_sellers_product(): void
    {
        $owner = $this->makeSeller();
        $product = $this->makeProduct($owner);
        $published = (int) $product->fresh()->published;

        $this->actingAs($this->makeSeller())
            ->post("/seller/products/{$product->id}/toggle-published")
            ->assertForbidden();

        $this->assertSame($published, (int) $product->fresh()->published);
    }

    public function test_owner_and_admin_can_edit_the_product(): void
    {
        $owner = $this->makeSeller();
        $product = $this->makeProduct($owner);

        $this->actingAs($owner)->get("/seller/products/{$product->id}/edit")->assertOk();

        $admin = User::factory()->create(['user_type' => 'admin']);
        $this->assertTrue(Gate::forUser($admin)->allows('update', $product));
    }

    // ── Pedidos ──────────────────────────────────────────────────

    public function test_seller_does_not_see_another_sellers_orders(): void
    {
        $owner = $this->makeSeller();
        $intruder = $this->makeSeller();
        $order = $this->makePaidOrder($this->makeCustomer(), $owner);

        $this->actingAs($owner)->get('/seller/orders')->assertOk()->assertSee($order->code);
        $this->actingAs($intruder)->get('/seller/orders')->assertOk()->assertDontSee($order->code);

        $this->actingAs($intruder)->get("/orders/{$order->id}")->assertNotFound();
    }

    public function test_seller_cannot_move_another_sellers_order(): void
    {
        $owner = $this->makeSeller();
        $intruder = $this->makeSeller();
        $order = $this->makePaidOrder($this->makeCustomer(), $owner);

        $this->actingAs($intruder)->post("/seller/orders/{$order->id}/confirm")->assertNotFound();
        $this->assertSame('pending', $order->fresh()->delivery_status);

        $order->update(['delivery_status' => 'confirmed']);
        $this->actingAs($intruder)->post("/orders/{$order->id}/send-to-warehouse")->assertNotFound();
        $this->assertSame('confirmed', $order->fresh()->delivery_status);
    }

    public function test_order_is_visible_to_its_buyer_its_seller_and_admin_only(): void
    {
        $buyer = $this->makeCustomer();
        $seller = $this->makeSeller();
        $order = $this->makePaidOrder($buyer, $seller);

        $this->actingAs($buyer)->get("/orders/{$order->id}")->assertOk();
        $this->actingAs($seller)->get("/orders/{$order->id}")->assertOk();
        $this->actingAs($this->makeCustomer())->get("/orders/{$order->id}")->assertNotFound();

        $admin = User::factory()->create(['user_type' => 'admin']);
        $this->assertTrue(Gate::forUser($admin)->allows('view', $order));
    }

    public function test_order_lines_belong_to_their_seller(): void
    {
        $buyer = $this->makeCustomer();
        $seller = $this->makeSeller();
        $intruder = $this->makeSeller();
        $detail = $this->makePaidOrder($buyer, $seller)->orderDetails()->first();

        $this->assertTrue(Gate::forUser($seller)->allows('update', $detail));
        $this->assertTrue(Gate::forUser($buyer)->allows('view', $detail));
        $this->assertFalse(Gate::forUser($buyer)->allows('update', $detail));
        $this->assertFalse(Gate::forUser($intruder)->allows('view', $detail));
        $this->assertFalse(Gate::forUser($intruder)->allows('update', $detail));
    }

    // ── Tiendas ──────────────────────────────────────────────────

    public function test_a_seller_cannot_approve_a_shop_not_even_their_own(): void
    {
        $seller = $this->makePendingSeller();
        $shop = $seller->shop;

        $this->assertTrue(Gate::forUser($seller)->allows('update', $shop));
        $this->assertFalse(Gate::forUser($seller)->allows('approve', $shop));
        $this->assertFalse(Gate::forUser($this->makeSeller())->allows('view', $shop));
    }
}
