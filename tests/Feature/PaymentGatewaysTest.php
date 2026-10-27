<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\Payments\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Stripe\PaymentIntent;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

class PaymentGatewaysTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    // ── Stripe ───────────────────────────────────────────────────

    public function test_stripe_intent_returns_client_secret_to_order_owner(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makePendingOrder($customer, $this->makeSeller(), 80.00);

        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldReceive('createIntent')
                ->once()
                ->withArgs(fn (int $cents) => $cents === 8000)
                ->andReturn(PaymentIntent::constructFrom([
                    'id' => 'pi_new',
                    'client_secret' => 'cs_test_abc',
                ]));
        });

        $this->actingAs($customer)
            ->withSession([CheckoutService::ORDER_SESSION_KEY => $order->id])
            ->postJson('/payments/stripe/intent')
            ->assertOk()
            ->assertJson(['clientSecret' => 'cs_test_abc']);

        // El id queda guardado para poder reutilizar el intent en la siguiente visita
        $this->assertSame('pi_new', $order->refresh()->payment_intent_id);
    }

    public function test_stripe_intent_is_reused_instead_of_creating_a_second_one(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makePendingOrder($customer, $this->makeSeller(), 80.00);
        $order->update(['payment_intent_id' => 'pi_existing']);

        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldReceive('retrieveIntent')
                ->once()
                ->with('pi_existing')
                ->andReturn(PaymentIntent::constructFrom([
                    'id' => 'pi_existing',
                    'status' => 'requires_payment_method',
                    'amount' => 8000,
                    'client_secret' => 'cs_test_existing',
                ]));
            $mock->shouldNotReceive('updateIntent');
            $mock->shouldNotReceive('createIntent');
        });

        $this->actingAs($customer)
            ->withSession([CheckoutService::ORDER_SESSION_KEY => $order->id])
            ->postJson('/payments/stripe/intent')
            ->assertOk()
            ->assertJson(['clientSecret' => 'cs_test_existing']);

        $this->assertSame('pi_existing', $order->refresh()->payment_intent_id);
    }

    public function test_stripe_intent_updates_the_amount_when_the_cart_total_changed(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makePendingOrder($customer, $this->makeSeller(), 95.50);
        $order->update(['payment_intent_id' => 'pi_existing']);

        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldReceive('retrieveIntent')
                ->once()
                ->andReturn(PaymentIntent::constructFrom([
                    'id' => 'pi_existing',
                    'status' => 'requires_payment_method',
                    'amount' => 8000,
                    'client_secret' => 'cs_stale',
                ]));
            $mock->shouldReceive('updateIntent')
                ->once()
                ->with('pi_existing', ['amount' => 9550])
                ->andReturn(PaymentIntent::constructFrom([
                    'id' => 'pi_existing',
                    'status' => 'requires_payment_method',
                    'amount' => 9550,
                    'client_secret' => 'cs_fresh',
                ]));
            $mock->shouldNotReceive('createIntent');
        });

        $this->actingAs($customer)
            ->withSession([CheckoutService::ORDER_SESSION_KEY => $order->id])
            ->postJson('/payments/stripe/intent')
            ->assertOk()
            ->assertJson(['clientSecret' => 'cs_fresh']);
    }

    public function test_stripe_intent_conflicts_when_a_payment_is_already_in_flight(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makePendingOrder($customer, $this->makeSeller(), 80.00);
        $order->update(['payment_intent_id' => 'pi_processing']);

        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldReceive('retrieveIntent')
                ->once()
                ->andReturn(PaymentIntent::constructFrom([
                    'id' => 'pi_processing',
                    'status' => 'processing',
                    'amount' => 8000,
                ]));
            // Crear otro intent aquí permitiría un doble cobro
            $mock->shouldNotReceive('createIntent');
        });

        $this->actingAs($customer)
            ->withSession([CheckoutService::ORDER_SESSION_KEY => $order->id])
            ->postJson('/payments/stripe/intent')
            ->assertStatus(409);
    }

    public function test_stripe_intent_falls_back_to_a_new_one_when_the_stored_id_is_unusable(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makePendingOrder($customer, $this->makeSeller(), 80.00);
        $order->update(['payment_intent_id' => 'pi_from_another_account']);

        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldReceive('retrieveIntent')
                ->once()
                ->andThrow(new \Stripe\Exception\InvalidRequestException('No such payment_intent'));
            $mock->shouldReceive('createIntent')
                ->once()
                ->andReturn(PaymentIntent::constructFrom([
                    'id' => 'pi_replacement',
                    'client_secret' => 'cs_replacement',
                ]));
        });

        $this->actingAs($customer)
            ->withSession([CheckoutService::ORDER_SESSION_KEY => $order->id])
            ->postJson('/payments/stripe/intent')
            ->assertOk()
            ->assertJson(['clientSecret' => 'cs_replacement']);

        $this->assertSame('pi_replacement', $order->refresh()->payment_intent_id);
    }

    public function test_stripe_intent_never_touches_another_users_order(): void
    {
        $seller = $this->makeSeller();
        $victimOrder = $this->makePendingOrder($this->makeCustomer(), $seller);

        $intruder = $this->makeCustomer();
        $this->addToCart($intruder, $this->makeProduct($seller, 30.00));

        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldReceive('createIntent')
                ->once()
                ->withArgs(fn (int $cents) => $cents === 3000)
                ->andReturn(PaymentIntent::constructFrom([
                    'id' => 'pi_intruder',
                    'client_secret' => 'cs_intruder',
                ]));
        });

        // Aunque la sesión apunte al pedido ajeno, se cobra uno propio
        $this->actingAs($intruder)
            ->withSession([CheckoutService::ORDER_SESSION_KEY => $victimOrder->id])
            ->postJson('/payments/stripe/intent')
            ->assertOk();

        $this->assertNull($victimOrder->refresh()->payment_intent_id);
        $this->assertSame(2, Order::count());
    }

    public function test_stripe_intent_starts_a_new_order_when_the_previous_one_was_paid(): void
    {
        Notification::fake();

        $customer = $this->makeCustomer();
        $order = $this->makePendingOrder($customer, $this->makeSeller());
        $order->markPaid('stripe', 'pi_prev');

        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldReceive('createIntent')->once()->andReturn(PaymentIntent::constructFrom([
                'id' => 'pi_second',
                'client_secret' => 'cs_second',
            ]));
        });

        $this->actingAs($customer)
            ->withSession([CheckoutService::ORDER_SESSION_KEY => $order->id])
            ->postJson('/payments/stripe/intent')
            ->assertOk();

        $this->assertSame(2, Order::count());
        $this->assertSame('pi_prev', $order->refresh()->payment_reference);
        $this->assertNotSame($order->id, (int) session(CheckoutService::ORDER_SESSION_KEY));
    }

    public function test_stripe_webhook_with_valid_signature_marks_order_paid(): void
    {
        Notification::fake();
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $customer = $this->makeCustomer();
        $seller = $this->makeSeller();
        $order = $this->makePendingOrder($customer, $seller, 100.00);
        $this->addToCart($customer, $this->makeProduct($seller));

        $response = $this->sendSignedStripeWebhook([
            'id' => 'evt_test',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => [
                'id' => 'pi_hook_1',
                'object' => 'payment_intent',
                'metadata' => ['order_id' => (string) $order->id],
            ]],
        ], 'whsec_test');

        $response->assertOk()->assertJson(['received' => true]);

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertSame('pi_hook_1', $order->payment_reference);
        $this->assertSame(0, Cart::where('user_id', $customer->id)->count());
    }

    public function test_stripe_webhook_rejects_invalid_signature(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $this->call(
            'POST',
            '/webhooks/stripe',
            server: ['HTTP_STRIPE_SIGNATURE' => 't=1,v1=invalid', 'CONTENT_TYPE' => 'application/json'],
            content: '{}',
        )->assertStatus(400);
    }

    public function test_stripe_webhook_payment_failed_marks_order_fallido(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $order = $this->makePendingOrder($this->makeCustomer(), $this->makeSeller());

        $this->sendSignedStripeWebhook([
            'id' => 'evt_fail',
            'type' => 'payment_intent.payment_failed',
            'data' => ['object' => [
                'id' => 'pi_hook_2',
                'object' => 'payment_intent',
                'metadata' => ['order_id' => (string) $order->id],
            ]],
        ], 'whsec_test')->assertOk();

        $order->refresh();
        $this->assertSame('fallido', $order->status);
        $this->assertFalse($order->isPaid());
    }

    // ── Stock ────────────────────────────────────────────────────

    public function test_mark_paid_deducts_stock_only_once(): void
    {
        Notification::fake();

        $customer = $this->makeCustomer();
        $seller = $this->makeSeller();
        $order = $this->makePendingOrder($customer, $seller);

        $detail = $order->orderDetails()->first();
        $detail->update(['quantity' => 3, 'variation' => 'M']);

        // variant es derivado de size/color desde que existen las variantes
        // estructuradas, así que las filas se crean por talla.
        $product = $detail->product;
        $product->stocks()->create(['size' => 'S', 'qty' => 10, 'price' => 100]);
        $product->stocks()->create(['size' => 'M', 'qty' => 10, 'price' => 100]);

        $order->markPaid('stripe', 'pi_stock');

        $this->assertSame(10, $product->stocks()->where('variant', 'S')->first()->qty);
        $this->assertSame(7, $product->stocks()->where('variant', 'M')->first()->qty);
        $this->assertSame(3, $product->fresh()->num_of_sale);

        // Second call is a no-op: stock is not deducted twice
        $order->refresh()->markPaid('stripe', 'pi_stock');
        $this->assertSame(7, $product->stocks()->where('variant', 'M')->first()->qty);
    }

    public function test_mark_paid_without_variation_deducts_first_stock_and_never_goes_negative(): void
    {
        Notification::fake();

        $customer = $this->makeCustomer();
        $seller = $this->makeSeller();
        $order = $this->makePendingOrder($customer, $seller);

        $detail = $order->orderDetails()->first();
        $detail->update(['quantity' => 5]);
        $detail->product->stocks()->create(['variant' => null, 'qty' => 2, 'price' => 100]);

        $order->markPaid('payphone', 'PP-stock');

        $this->assertSame(0, $detail->product->stocks()->first()->qty);
    }

    /** Signs the payload the same way Stripe does: v1 = HMAC-SHA256("{t}.{payload}"). */
    private function sendSignedStripeWebhook(array $event, string $secret)
    {
        $payload = json_encode($event);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        return $this->call(
            'POST',
            '/webhooks/stripe',
            server: [
                'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
                'CONTENT_TYPE' => 'application/json',
            ],
            content: $payload,
        );
    }
}
