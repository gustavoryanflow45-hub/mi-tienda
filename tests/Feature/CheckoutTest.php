<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Notifications\SellerNewOrderNotification;
use App\Services\Payments\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Stripe\PaymentIntent;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/checkout')->assertRedirect(route('login'));
    }

    public function test_empty_cart_redirects_home(): void
    {
        $customer = $this->makeCustomer();

        $this->actingAs($customer)
            ->get('/checkout')
            ->assertRedirect('/')
            ->assertSessionHas('error');

        $this->assertSame(0, Order::count());
    }

    public function test_checkout_creates_pending_order_with_details(): void
    {
        $customer = $this->makeCustomer();
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, 50.00);
        $this->addToCart($customer, $product, quantity: 2);

        $response = $this->actingAs($customer)->get('/checkout');

        $response->assertOk()->assertViewIs('checkout.index');

        $order = Order::sole();
        $this->assertSame($customer->id, $order->user_id);
        $this->assertSame('pendiente', $order->status);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame(100.00, (float) $order->grand_total);
        $this->assertSame($order->id, (int) session('checkout_order_id'));

        $detail = $order->orderDetails()->sole();
        $this->assertSame($seller->id, $detail->seller_id);
        $this->assertSame($product->id, $detail->product_id);
        $this->assertSame(2, $detail->quantity);
        $this->assertSame(50.00, (float) $detail->price);
    }

    public function test_checkout_reuses_pending_order_and_syncs_totals(): void
    {
        $customer = $this->makeCustomer();
        $product = $this->makeProduct($this->makeSeller(), 50.00);
        $cart = $this->addToCart($customer, $product, quantity: 1);

        $this->actingAs($customer)->get('/checkout');
        $firstOrderId = (int) session('checkout_order_id');

        $cart->update(['quantity' => 3]);
        $this->actingAs($customer)->get('/checkout');

        $this->assertSame(1, Order::count());
        $this->assertSame($firstOrderId, (int) session('checkout_order_id'));
        $this->assertSame(150.00, (float) Order::sole()->grand_total);
    }

    public function test_paid_order_is_replaced_by_a_fresh_one(): void
    {
        Notification::fake();

        $customer = $this->makeCustomer();
        $product = $this->makeProduct($this->makeSeller(), 50.00);
        $this->addToCart($customer, $product);

        $this->actingAs($customer)->get('/checkout');
        $firstOrder = Order::sole();
        $firstOrder->markPaid('stripe', 'pi_test');

        $this->actingAs($customer)->get('/checkout');

        $this->assertSame(2, Order::count());
        $this->assertNotSame($firstOrder->id, (int) session('checkout_order_id'));
    }

    public function test_gateway_defaults_to_kushki_on_localhost_and_accepts_override(): void
    {
        $customer = $this->makeCustomer();
        $this->addToCart($customer, $this->makeProduct($this->makeSeller()));

        // In non-production, localhost resolves to EC → Kushki
        $this->actingAs($customer)
            ->get('/checkout')
            ->assertViewHas('gateway', 'kushki')
            ->assertViewHas('detectedCountry', 'EC');

        $this->actingAs($customer)
            ->get('/checkout?gateway=stripe')
            ->assertViewHas('gateway', 'stripe');
    }

    public function test_success_is_forbidden_for_another_users_order(): void
    {
        $order = $this->makePendingOrder($this->makeCustomer(), $this->makeSeller());
        $intruder = $this->makeCustomer();

        $this->actingAs($intruder)
            ->get("/checkout/success/{$order->id}")
            ->assertForbidden();
    }

    public function test_success_verifies_stripe_intent_marks_paid_and_notifies_seller(): void
    {
        Notification::fake();

        $customer = $this->makeCustomer();
        $seller = $this->makeSeller();
        $order = $this->makePendingOrder($customer, $seller, 100.00);
        $this->addToCart($customer, $this->makeProduct($seller));

        $intent = PaymentIntent::constructFrom([
            'id' => 'pi_test_123',
            'status' => 'succeeded',
            'metadata' => ['order_id' => (string) $order->id],
        ]);
        $this->mock(StripeService::class, function ($mock) use ($intent) {
            $mock->shouldReceive('retrieveIntent')->once()->with('pi_test_123')->andReturn($intent);
        });

        $this->actingAs($customer)
            ->withSession(['checkout_order_id' => $order->id])
            ->get("/checkout/success/{$order->id}?payment_intent=pi_test_123")
            ->assertOk()
            ->assertSessionMissing('checkout_order_id');

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertSame('stripe', $order->payment_gateway);
        $this->assertSame('pi_test_123', $order->payment_reference);
        $this->assertSame('paid', $order->orderDetails()->sole()->payment_status);
        $this->assertSame(0, Cart::where('user_id', $customer->id)->count());

        Notification::assertSentTo($seller, SellerNewOrderNotification::class);
    }

    public function test_success_ignores_intent_belonging_to_another_order(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makePendingOrder($customer, $this->makeSeller());

        $intent = PaymentIntent::constructFrom([
            'id' => 'pi_test_999',
            'status' => 'succeeded',
            'metadata' => ['order_id' => (string) ($order->id + 1)],
        ]);
        $this->mock(StripeService::class, function ($mock) use ($intent) {
            $mock->shouldReceive('retrieveIntent')->andReturn($intent);
        });

        $this->actingAs($customer)
            ->get("/checkout/success/{$order->id}?payment_intent=pi_test_999")
            ->assertOk();

        $this->assertFalse($order->refresh()->isPaid());
    }
}
