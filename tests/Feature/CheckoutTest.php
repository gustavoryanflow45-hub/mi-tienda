<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Notifications\SellerNewOrderNotification;
use App\Services\CheckoutService;
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

    /**
     * Abrir el checkout no debe registrar nada: si el comprador vuelve atrás
     * no le queda un pedido en el historial ni al vendedor una venta.
     */
    public function test_opening_checkout_does_not_create_an_order(): void
    {
        $customer = $this->makeCustomer();
        $product = $this->makeProduct($this->makeSeller(), 50.00);
        $this->addToCart($customer, $product, quantity: 2);

        $this->actingAs($customer)
            ->get('/checkout')
            ->assertOk()
            ->assertViewIs('checkout.index')
            ->assertViewHas('total', 100.00);

        $this->assertSame(0, Order::count());
        $this->assertNull(session(CheckoutService::ORDER_SESSION_KEY));
    }

    public function test_checkout_totals_include_the_shipping_cost_of_each_line(): void
    {
        $customer = $this->makeCustomer();
        $seller = $this->makeSeller();
        $this->addToCart($customer, $this->makeProduct($seller, 50.00), quantity: 2, price: 50.00, shippingCost: 4.50);
        $this->addToCart($customer, $this->makeProduct($seller, 20.00), quantity: 1, price: 20.00, shippingCost: 2.00);

        $this->actingAs($customer)
            ->get('/checkout')
            ->assertOk()
            ->assertViewHas('subtotal', 120.00)
            ->assertViewHas('shippingTotal', 6.50)
            ->assertViewHas('total', 126.50);
    }

    public function test_checkout_prefills_shipping_from_the_default_address(): void
    {
        $customer = $this->makeCustomer();
        $customer->addresses()->create([
            'full_name' => 'Juan Pérez',
            'phone' => '0991234567',
            'email' => 'juan@example.com',
            'address' => 'Av. Amazonas N24-03',
            'city' => 'Quito',
            'country' => 'Ecuador',
            'is_default' => 1,
        ]);
        $this->addToCart($customer, $this->makeProduct($this->makeSeller()));

        $response = $this->actingAs($customer)->get('/checkout')->assertOk();

        $shipping = $response->viewData('shipping');
        $this->assertSame('Juan Pérez', $shipping['full_name']);
        $this->assertSame('0991234567', $shipping['phone']);
        $this->assertSame('Av. Amazonas N24-03', $shipping['address']);
    }

    /**
     * Al guardar el envío todavía no hay pedido: los datos quedan en sesión
     * y los recoge el pedido cuando se crea, al iniciar el pago.
     */
    public function test_shipping_endpoint_saves_data_for_the_future_order(): void
    {
        $customer = $this->makeCustomer();
        $this->addToCart($customer, $this->makeProduct($this->makeSeller()));

        $payload = [
            'full_name' => 'María López',
            'phone' => '0987654321',
            'email' => 'maria@example.com',
            'address' => 'Calle Larga 123',
            'city' => 'Cuenca',
            'state' => 'Azuay',
            'country' => 'Ecuador',
            'postal_code' => '010101',
        ];

        $this->actingAs($customer)
            ->postJson('/checkout/shipping', $payload)
            ->assertOk()
            ->assertSessionHas(CheckoutService::SHIPPING_SESSION_KEY, $payload);

        $this->assertSame(0, Order::count());

        $address = $customer->addresses()->where('is_default', 1)->sole();
        $this->assertSame('María López', $address->full_name);
        $this->assertSame('0987654321', $address->phone);
    }

    public function test_shipping_endpoint_requires_complete_data(): void
    {
        $customer = $this->makeCustomer();
        $this->addToCart($customer, $this->makeProduct($this->makeSeller()));

        $this->actingAs($customer)
            ->postJson('/checkout/shipping', ['full_name' => 'Solo Nombre'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone', 'email', 'address', 'city']);
    }

    /** El pedido nace al iniciar el pago, con el envío ya sumado al total. */
    public function test_starting_the_payment_creates_the_order_from_the_cart(): void
    {
        $customer = $this->makeCustomer();
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, 50.00);
        $this->addToCart($customer, $product, quantity: 2, price: 50.00, shippingCost: 4.50);

        $this->mockCreatedIntent('pi_new', 'cs_new');

        $this->actingAs($customer)
            ->withSession([CheckoutService::SHIPPING_SESSION_KEY => $this->completeShippingAddress($customer)])
            ->postJson('/payments/stripe/intent')
            ->assertOk()
            ->assertJson(['clientSecret' => 'cs_new']);

        $order = Order::sole();
        $this->assertSame($customer->id, $order->user_id);
        $this->assertSame('pendiente', $order->status);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame(100.00, (float) $order->subtotal);
        $this->assertSame(4.50, (float) $order->shipping_total);
        $this->assertSame(104.50, (float) $order->grand_total);
        $this->assertTrue($order->hasCompleteShippingInfo());
        $this->assertSame($order->id, (int) session(CheckoutService::ORDER_SESSION_KEY));

        $detail = $order->orderDetails()->sole();
        $this->assertSame($seller->id, $detail->seller_id);
        $this->assertSame($product->id, $detail->product_id);
        $this->assertSame(2, $detail->quantity);
        $this->assertSame(50.00, (float) $detail->price);
        $this->assertSame(4.50, (float) $detail->shipping_cost);
    }

    /** Un segundo intento reutiliza el pedido en curso y lo resincroniza. */
    public function test_a_second_payment_attempt_reuses_the_pending_order(): void
    {
        $customer = $this->makeCustomer();
        $product = $this->makeProduct($this->makeSeller(), 50.00);
        $cart = $this->addToCart($customer, $product, quantity: 1, price: 50.00);

        $this->mockCreatedIntent('pi_a', 'cs_a');
        $this->actingAs($customer)->postJson('/payments/stripe/intent')->assertOk();
        $firstOrderId = (int) session(CheckoutService::ORDER_SESSION_KEY);

        $cart->update(['quantity' => 3]);

        $this->mockCreatedIntent('pi_b', 'cs_b');
        $this->actingAs($customer)
            ->withSession([CheckoutService::ORDER_SESSION_KEY => $firstOrderId])
            ->postJson('/payments/stripe/intent')
            ->assertOk();

        $this->assertSame(1, Order::count());
        $order = Order::sole();
        $this->assertSame($firstOrderId, $order->id);
        $this->assertSame(150.00, (float) $order->grand_total);
        $this->assertSame(3, $order->orderDetails()->sole()->quantity);
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

        $intent = PaymentIntent::constructFrom([
            'id' => 'pi_test_123',
            'status' => 'succeeded',
            'metadata' => ['order_id' => (string) $order->id],
        ]);
        $this->mock(StripeService::class, function ($mock) use ($intent) {
            $mock->shouldReceive('retrieveIntent')->once()->with('pi_test_123')->andReturn($intent);
        });

        $this->actingAs($customer)
            ->withSession([CheckoutService::ORDER_SESSION_KEY => $order->id])
            ->get("/checkout/success/{$order->id}?payment_intent=pi_test_123")
            ->assertOk()
            ->assertSessionMissing(CheckoutService::ORDER_SESSION_KEY);

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

    private function mockCreatedIntent(string $id, string $clientSecret): void
    {
        $this->mock(StripeService::class, function ($mock) use ($id, $clientSecret) {
            $mock->shouldReceive('createIntent')->andReturn(PaymentIntent::constructFrom([
                'id' => $id,
                'client_secret' => $clientSecret,
            ]));
        });
    }
}
