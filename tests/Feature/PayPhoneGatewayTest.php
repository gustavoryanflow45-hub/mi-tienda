<?php

namespace Tests\Feature;

use App\Http\Controllers\Payments\PayPhoneController;
use App\Models\Cart;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\Payments\PayPhoneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

/**
 * PayPhone, la pasarela de Ecuador: prepare al pulsar "Pagar" y confirm a la
 * vuelta (sin confirm en 5 minutos PayPhone revierte el cobro). La API se
 * simula con Http::fake.
 */
class PayPhoneGatewayTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    private const PREPARE = PayPhoneService::BASE_URL.'/api/button/Prepare';

    private const CONFIRM = PayPhoneService::BASE_URL.'/api/button/V2/Confirm';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.payphone.token' => 'token-de-prueba',
            'services.payphone.store_id' => 'store-123',
            'app.ec_iva_rate' => 0.15,
        ]);
    }

    protected function fakePrepare(): void
    {
        Http::fake([
            self::PREPARE => Http::response([
                'paymentId' => 'pay-1',
                'payWithCard' => 'https://pay.payphonetodoesposible.com/Anonymous/Index?paymentId=pay-1',
                'payWithPayPhone' => 'https://pay.payphonetodoesposible.com/PayPhone/Index?paymentId=pay-1',
            ]),
        ]);
    }

    protected function fakeConfirm(array $body, int $status = 200): void
    {
        Http::fake([self::CONFIRM => Http::response($body, $status)]);
    }

    protected function approved(Order $order, string $clientTxId, array $overrides = []): array
    {
        return $overrides + [
            'statusCode' => PayPhoneService::STATUS_APPROVED,
            'transactionStatus' => 'Approved',
            'transactionId' => 987654,
            'clientTransactionId' => $clientTxId,
            'amount' => (int) round($order->grand_total * 100),
            'authorizationCode' => 'W123',
        ];
    }

    // ── Prepare ──────────────────────────────────────────────────

    public function test_prepare_sends_the_iva_split_in_cents_and_returns_the_card_url(): void
    {
        $this->fakePrepare();
        $customer = $this->makeCustomer();
        $order = $this->makePendingOrder($customer, $this->makeSeller(), 115.00);

        $this->actingAs($customer)
            ->withSession([CheckoutService::ORDER_SESSION_KEY => $order->id])
            ->postJson('/payments/payphone/prepare', ['method' => 'card'])
            ->assertOk()
            ->assertJsonPath('redirectUrl', 'https://pay.payphonetodoesposible.com/Anonymous/Index?paymentId=pay-1');

        // 115.00 con IVA 15 % → base 100.00 + IVA 15.00, en centavos.
        Http::assertSent(fn (HttpRequest $request) => $request->url() === self::PREPARE
            && $request->hasHeader('Authorization', 'Bearer token-de-prueba')
            && $request['amount'] === 11500
            && $request['amountWithTax'] === 10000
            && $request['tax'] === 1500
            && $request['amountWithoutTax'] === 0
            && $request['storeId'] === 'store-123'
            && $request['currency'] === 'USD'
            && $request['responseUrl'] === route('payments.payphone.return')
            && $request['cancellationUrl'] === route('payments.payphone.cancel')
            && str_starts_with($request['clientTransactionId'], 'GS'.$order->id.'-'));

        $this->assertFalse($order->refresh()->isPaid());
    }

    public function test_prepare_can_send_the_buyer_to_the_payphone_app(): void
    {
        $this->fakePrepare();
        $customer = $this->makeCustomer();
        $this->makePendingOrder($customer, $this->makeSeller());

        $this->actingAs($customer)
            ->postJson('/payments/payphone/prepare', ['method' => 'app'])
            ->assertOk()
            ->assertJsonPath('redirectUrl', 'https://pay.payphonetodoesposible.com/PayPhone/Index?paymentId=pay-1');
    }

    public function test_every_attempt_gets_a_new_client_transaction_id(): void
    {
        $this->fakePrepare();
        $customer = $this->makeCustomer();
        $this->makePendingOrder($customer, $this->makeSeller());

        $this->actingAs($customer)->postJson('/payments/payphone/prepare')->assertOk();
        $this->actingAs($customer)->postJson('/payments/payphone/prepare')->assertOk();

        $ids = Http::recorded()->map(fn ($pair) => $pair[0]['clientTransactionId']);
        $this->assertCount(2, $ids->unique());
    }

    public function test_prepare_with_an_empty_cart_is_rejected(): void
    {
        Http::fake();

        $this->actingAs($this->makeCustomer())
            ->postJson('/payments/payphone/prepare')
            ->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_prepare_without_credentials_fails_cleanly(): void
    {
        Http::fake();
        config(['services.payphone.token' => null]);
        $customer = $this->makeCustomer();
        $this->makePendingOrder($customer, $this->makeSeller());

        $this->actingAs($customer)
            ->postJson('/payments/payphone/prepare')
            ->assertStatus(503)
            ->assertJsonStructure(['message']);

        Http::assertNothingSent();
    }

    public function test_prepare_reports_a_payphone_error(): void
    {
        Http::fake([self::PREPARE => Http::response(['message' => 'storeId inválido'], 400)]);
        $customer = $this->makeCustomer();
        $this->makePendingOrder($customer, $this->makeSeller());

        $this->actingAs($customer)
            ->postJson('/payments/payphone/prepare')
            ->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    // ── Vuelta y confirmación ────────────────────────────────────

    public function test_an_approved_payment_is_confirmed_and_marks_the_order_paid(): void
    {
        Notification::fake();
        $customer = $this->makeCustomer();
        $order = $this->makePendingOrder($customer, $this->makeSeller(), 115.00);
        $txId = PayPhoneController::transactionIdFor($order);
        $this->fakeConfirm($this->approved($order, $txId));

        $this->actingAs($customer)
            ->get('/payments/payphone/return?id=555&clientTransactionId='.$txId)
            ->assertRedirect(route('checkout.success', $order));

        Http::assertSent(fn (HttpRequest $request) => $request->url() === self::CONFIRM
            && $request['id'] === 555
            && $request['clientTxId'] === $txId);

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertSame('payphone', $order->payment_gateway);
        $this->assertSame('987654', $order->payment_reference);
        $this->assertSame(0, Cart::where('user_id', $customer->id)->count());
    }

    public function test_a_cancelled_payment_leaves_the_order_unpaid(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makePendingOrder($customer, $this->makeSeller());
        $txId = PayPhoneController::transactionIdFor($order);
        $this->fakeConfirm($this->approved($order, $txId, [
            'statusCode' => PayPhoneService::STATUS_CANCELED,
            'transactionStatus' => 'Canceled',
        ]));

        $this->actingAs($customer)
            ->get('/payments/payphone/return?id=1&clientTransactionId='.$txId)
            ->assertRedirect(route('checkout'))
            ->assertSessionHas('warning');

        $this->assertFalse($order->refresh()->isPaid());
        $this->assertSame(1, Cart::where('user_id', $customer->id)->count());
    }

    public function test_an_approved_amount_that_does_not_match_the_order_is_not_accepted(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makePendingOrder($customer, $this->makeSeller(), 100.00);
        $txId = PayPhoneController::transactionIdFor($order);
        $this->fakeConfirm($this->approved($order, $txId, ['amount' => 100]));

        $this->actingAs($customer)
            ->get('/payments/payphone/return?id=1&clientTransactionId='.$txId)
            ->assertRedirect(route('checkout'))
            ->assertSessionHas('error');

        $this->assertFalse($order->refresh()->isPaid());
    }

    public function test_a_confirm_failure_leaves_the_order_unpaid(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makePendingOrder($customer, $this->makeSeller());
        $this->fakeConfirm(['message' => 'error'], 500);

        $this->actingAs($customer)
            ->get('/payments/payphone/return?id=1&clientTransactionId='.PayPhoneController::transactionIdFor($order))
            ->assertRedirect(route('checkout'))
            ->assertSessionHas('error');

        $this->assertFalse($order->refresh()->isPaid());
    }

    public function test_a_tampered_transaction_id_is_ignored_without_calling_payphone(): void
    {
        Http::fake();

        $this->actingAs($this->makeCustomer())
            ->get('/payments/payphone/return?id=1&clientTransactionId=GS1;DROP')
            ->assertRedirect(route('checkout'))
            ->assertSessionHas('error');

        Http::assertNothingSent();
    }

    public function test_the_payment_is_confirmed_even_if_the_session_was_lost(): void
    {
        Notification::fake();
        $order = $this->makePendingOrder($this->makeCustomer(), $this->makeSeller());
        $txId = PayPhoneController::transactionIdFor($order);
        $this->fakeConfirm($this->approved($order, $txId));

        $this->get('/payments/payphone/return?id=1&clientTransactionId='.$txId)
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', route('orders.show', $order->id));

        $this->assertTrue($order->refresh()->isPaid());
    }

    public function test_coming_back_twice_does_not_confirm_again(): void
    {
        Notification::fake();
        $customer = $this->makeCustomer();
        $order = $this->makePendingOrder($customer, $this->makeSeller());
        $order->markPaid('payphone', '111');
        Http::fake();

        $this->actingAs($customer)
            ->get('/payments/payphone/return?id=1&clientTransactionId='.PayPhoneController::transactionIdFor($order))
            ->assertRedirect(route('checkout.success', $order));

        Http::assertNothingSent();
    }

    public function test_cancelling_on_payphone_returns_to_checkout(): void
    {
        $this->actingAs($this->makeCustomer())
            ->get('/payments/payphone/cancel')
            ->assertRedirect(route('checkout'))
            ->assertSessionHas('warning');
    }

    // ── Checkout ─────────────────────────────────────────────────

    public function test_checkout_shows_payphone_in_ecuador_and_stripe_on_request(): void
    {
        $customer = $this->makeCustomer();
        $this->addToCart($customer, $this->makeProduct($this->makeSeller()));

        // Fuera de producción, localhost se resuelve como EC.
        $this->actingAs($customer)->get('/checkout')
            ->assertViewHas('gateway', 'payphone')
            ->assertSee('data-payphone-method="card"', false)
            ->assertSee('data-payphone-method="app"', false)
            ->assertDontSee('js.stripe.com', false);

        $this->actingAs($customer)->get('/checkout?gateway=stripe')
            ->assertViewHas('gateway', 'stripe')
            ->assertSee('js.stripe.com', false)
            ->assertDontSee('data-payphone-method="card"', false);

        // Kushki ya no es una pasarela válida: cae en la detectada.
        $this->actingAs($customer)->get('/checkout?gateway=kushki')
            ->assertViewHas('gateway', 'payphone');
    }

    public function test_amounts_always_add_up_to_the_total(): void
    {
        foreach ([0.01, 1.00, 9.99, 33.33, 115.00, 1234.57] as $total) {
            $a = PayPhoneService::amounts($total, 0.15);

            $this->assertSame((int) round($total * 100), $a['amount']);
            $this->assertSame($a['amount'], $a['amountWithoutTax'] + $a['amountWithTax'] + $a['tax']);
        }
    }
}
