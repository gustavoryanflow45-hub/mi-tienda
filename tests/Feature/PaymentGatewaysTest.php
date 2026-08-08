<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Services\Payments\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Stripe\PaymentIntent;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

class PaymentGatewaysTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    // ── Kushki ───────────────────────────────────────────────────

    private function kushkiPayload(): array
    {
        return [
            'token' => 'tok_test',
            'document_type' => 'CC',
            'document_number' => '0102030405',
            'email' => 'cliente@example.com',
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
        ];
    }

    public function test_kushki_charge_marks_order_paid_with_correct_iva_split(): void
    {
        Notification::fake();
        Http::fake([
            'api-uat.kushkipagos.com/*' => Http::response(['ticketNumber' => 'TK-123'], 200),
        ]);

        $customer = $this->makeCustomer();
        $seller = $this->makeSeller();
        $order = $this->makePendingOrder($customer, $seller, 115.00);
        $this->addToCart($customer, $this->makeProduct($seller));

        $this->actingAs($customer)
            ->withSession(['checkout_order_id' => $order->id])
            ->postJson('/payments/kushki/charge', $this->kushkiPayload())
            ->assertOk()
            ->assertJson(['status' => 'success', 'ticket' => 'TK-123']);

        // With ec_iva_rate = 0.15, a 115.00 total splits into 100.00 base + 15.00 IVA
        Http::assertSent(function (HttpRequest $request) {
            return str_contains($request->url(), '/card/v1/charges')
                && $request['amount']['subtotalIva'] === 100.00
                && $request['amount']['iva'] === 15.00
                && $request['amount']['currency'] === 'USD';
        });

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertSame('kushki', $order->payment_gateway);
        $this->assertSame('TK-123', $order->payment_reference);
        $this->assertSame(0, Cart::where('user_id', $customer->id)->count());
    }

    public function test_kushki_rejected_charge_returns_422_and_keeps_order_unpaid(): void
    {
        Http::fake([
            'api-uat.kushkipagos.com/*' => Http::response(['message' => 'Tarjeta rechazada'], 402),
        ]);

        $customer = $this->makeCustomer();
        $order = $this->makePendingOrder($customer, $this->makeSeller());

        $this->actingAs($customer)
            ->withSession(['checkout_order_id' => $order->id])
            ->postJson('/payments/kushki/charge', $this->kushkiPayload())
            ->assertStatus(422)
            ->assertJson(['status' => 'error', 'message' => 'Tarjeta rechazada']);

        $this->assertFalse($order->refresh()->isPaid());
    }

    public function test_kushki_charge_without_pending_order_is_rejected(): void
    {
        Http::fake();

        $this->actingAs($this->makeCustomer())
            ->postJson('/payments/kushki/charge', $this->kushkiPayload())
            ->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_kushki_webhook_marks_order_paid(): void
    {
        $order = $this->makePendingOrder($this->makeCustomer(), $this->makeSeller());

        $this->postJson('/webhooks/kushki', [
            'ticketNumber' => 'TK-777',
            'metadata' => ['order_id' => $order->id],
        ])->assertOk();

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertSame('TK-777', $order->payment_reference);
    }

    // ── Stripe ───────────────────────────────────────────────────

    public function test_stripe_intent_returns_client_secret_to_order_owner(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makePendingOrder($customer, $this->makeSeller(), 80.00);

        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldReceive('createIntent')
                ->once()
                ->withArgs(fn (int $cents) => $cents === 8000)
                ->andReturn(PaymentIntent::constructFrom(['client_secret' => 'cs_test_abc']));
        });

        $this->actingAs($customer)
            ->postJson('/payments/stripe/intent', ['order_id' => $order->id])
            ->assertOk()
            ->assertJson(['clientSecret' => 'cs_test_abc']);
    }

    public function test_stripe_intent_is_forbidden_for_strangers(): void
    {
        $order = $this->makePendingOrder($this->makeCustomer(), $this->makeSeller());

        $this->actingAs($this->makeCustomer())
            ->postJson('/payments/stripe/intent', ['order_id' => $order->id])
            ->assertForbidden();
    }

    public function test_stripe_intent_conflicts_when_order_already_paid(): void
    {
        Notification::fake();

        $customer = $this->makeCustomer();
        $order = $this->makePendingOrder($customer, $this->makeSeller());
        $order->markPaid('stripe', 'pi_prev');

        $this->actingAs($customer)
            ->postJson('/payments/stripe/intent', ['order_id' => $order->id])
            ->assertStatus(409);
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

        $order->markPaid('kushki', 'TK-stock');

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
