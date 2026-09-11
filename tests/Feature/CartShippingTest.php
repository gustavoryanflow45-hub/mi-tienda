<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

class CartShippingTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    public function test_cart_summary_separates_shipping_from_the_subtotal(): void
    {
        $customer = $this->makeCustomer();
        $seller = $this->makeSeller();
        $this->addToCart($customer, $this->makeProduct($seller, 50.00), quantity: 2, price: 50.00, shippingCost: 4.50);
        $this->addToCart($customer, $this->makeProduct($seller, 20.00), quantity: 1, price: 20.00, shippingCost: 2.00);

        $this->actingAs($customer)
            ->get('/cart')
            ->assertOk()
            ->assertViewHas('total', 120.00)
            ->assertViewHas('shippingTotal', 6.50)
            ->assertViewHas('grandTotal', 126.50);
    }

    /** El envío es fijo por línea: cambiar la cantidad no lo multiplica. */
    public function test_updating_the_quantity_keeps_the_shipping_flat(): void
    {
        $customer = $this->makeCustomer();
        $cart = $this->addToCart(
            $customer,
            $this->makeProduct($this->makeSeller(), 50.00),
            quantity: 1,
            price: 50.00,
            shippingCost: 4.50,
        );

        $this->actingAs($customer)
            ->postJson('/cart/update-quantity', ['cart_id' => $cart->id, 'quantity' => 3])
            ->assertOk()
            ->assertJson([
                'cart_total' => '150.00',
                'cart_shipping' => '4.50',
                'cart_grand' => '154.50',
            ]);
    }

    public function test_removing_a_line_drops_its_shipping_too(): void
    {
        $customer = $this->makeCustomer();
        $seller = $this->makeSeller();
        $keep = $this->addToCart($customer, $this->makeProduct($seller, 50.00), price: 50.00, shippingCost: 4.50);
        $drop = $this->addToCart($customer, $this->makeProduct($seller, 20.00), price: 20.00, shippingCost: 2.00);

        $this->actingAs($customer)
            ->postJson('/cart/removeFromCart', ['cart_id' => $drop->id])
            ->assertOk()
            ->assertJson([
                'cart_total' => '50.00',
                'cart_shipping' => '4.50',
                'cart_grand' => '54.50',
            ]);

        $this->assertDatabaseHas('carts', ['id' => $keep->id]);
    }

    public function test_mini_cart_renders_the_shipping_line(): void
    {
        $customer = $this->makeCustomer();
        $this->addToCart(
            $customer,
            $this->makeProduct($this->makeSeller(), 50.00),
            price: 50.00,
            shippingCost: 4.50,
        );

        $html = $this->actingAs($customer)->getJson('/cart/mini')->assertOk()->json('html');

        $this->assertStringContainsString('$4.50', $html);
        $this->assertStringContainsString('$54.50', $html);
    }
}
