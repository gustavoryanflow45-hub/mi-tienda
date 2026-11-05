<?php

namespace Tests\Feature\Api\V1;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use App\Services\Payments\PayPhoneCheckout;
use App\Services\Payments\PayPhoneService;
use App\Services\Payments\StripeService;
use App\Support\ApiAbilities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Stripe\PaymentIntent;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

/**
 * /api/v1/cart y /api/v1/checkout: el mismo carrito y las mismas reglas que
 * la web (CartService), el pedido pendiente sin sesión y el cobro con Stripe
 * y PayPhone compartiendo StripeCheckout / PayPhoneCheckout con la web.
 */
class CartCheckoutTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    private const PREPARE = PayPhoneService::BASE_URL.'/api/button/Prepare';

    private const CONFIRM = PayPhoneService::BASE_URL.'/api/button/V2/Confirm';

    protected User $customer;

    protected User $seller;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.payphone.token' => 'token-de-prueba',
            'services.payphone.store_id' => 'store-123',
            'services.stripe.key' => 'pk_test_123',
            'app.ec_iva_rate' => 0.15,
        ]);

        $this->customer = $this->makeCustomer();
        $this->seller = $this->makeSeller();
    }

    protected function api(?User $user = null, ?array $abilities = null): static
    {
        $user ??= $this->customer;
        $token = $user->createToken('test', $abilities ?? ApiAbilities::for($user), now()->addDay())->plainTextToken;

        // El guard de Sanctum recuerda al usuario entre peticiones del mismo test.
        $this->app['auth']->forgetGuards();

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    /** Zapatilla con tallas 40 y 41 en negro; la 41 agotada. */
    protected function sneaker(float $price = 50): Product
    {
        $product = $this->makeProduct($this->seller, $price);
        ProductStock::create(['product_id' => $product->id, 'size' => '40', 'color' => 'negro', 'price' => $price, 'qty' => 3]);
        ProductStock::create(['product_id' => $product->id, 'size' => '41', 'color' => 'negro', 'price' => $price, 'qty' => 0]);

        return $product;
    }

    protected function flat(float $price = 20, int $qty = 5): Product
    {
        $product = $this->makeProduct($this->seller, $price);
        ProductStock::create(['product_id' => $product->id, 'price' => $price, 'qty' => $qty]);

        return $product;
    }

    protected function shipping(): array
    {
        return [
            'full_name' => 'Ana Pérez', 'phone' => '0999999999', 'email' => 'ana@correo.com',
            'address' => 'Av. Amazonas 123', 'city' => 'Quito',
        ];
    }

    /** Carrito con una zapatilla y el pedido pendiente ya creado por la API. */
    protected function pendingOrder(float $price = 115): Order
    {
        $this->api()->postJson('/api/v1/cart/items', ['product_id' => $this->flat($price)->id])->assertCreated();
        $id = $this->api()->postJson('/api/v1/checkout/orders', $this->shipping())->assertCreated()->json('data.id');

        return Order::findOrFail($id);
    }

    // ── Carrito ──────────────────────────────────────────────────

    public function test_items_are_added_with_the_combination_key_and_described(): void
    {
        $product = $this->sneaker();

        $this->api()->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'variation' => '40-negro', 'quantity' => 2])
            ->assertCreated()
            ->assertJsonPath('data.count', 2)
            ->assertJsonPath('data.items.0.variation', '40-negro')
            ->assertJsonPath('data.items.0.variant.size', '40')
            ->assertJsonPath('data.items.0.variant.color', 'negro')
            ->assertJsonPath('data.items.0.available_qty', 3)
            ->assertJsonPath('data.totals.subtotal', 100)
            ->assertJsonPath('message', fn ($message) => filled($message));

        // Mismo carrito que la web.
        $this->assertSame(2, (int) Cart::where('user_id', $this->customer->id)->sum('quantity'));
    }

    public function test_the_cart_applies_the_same_rules_as_the_web(): void
    {
        $sneaker = $this->sneaker();

        // Sin elegir combinación, una que no existe, una agotada y pasarse del stock.
        foreach ([
            ['variation' => null],
            ['variation' => '99-rojo'],
            ['variation' => '41-negro'],
            ['variation' => '40-negro', 'quantity' => 4],
        ] as $input) {
            $this->api()->postJson('/api/v1/cart/items', $input + ['product_id' => $sneaker->id])
                ->assertStatus(422)
                ->assertJsonPath('code', 'business_rule');
        }

        // Un producto vetado por moderación no entra (404, como en la web).
        $vetoed = $this->flat();
        $vetoed->forceFill(['approved' => 0])->save();
        $this->api()->postJson('/api/v1/cart/items', ['product_id' => $vetoed->id])->assertNotFound();

        // Producto sin variantes: variation "" (lo que da la ficha) vale.
        $this->api()->postJson('/api/v1/cart/items', ['product_id' => $this->flat()->id, 'variation' => ''])->assertCreated();

        $this->assertSame(1, Cart::where('user_id', $this->customer->id)->count());
    }

    public function test_quantity_is_capped_and_lines_removed(): void
    {
        $item = $this->addToCart($this->customer, $flat = $this->flat(20, 5));

        $this->api()->patchJson("/api/v1/cart/items/{$item->id}", ['quantity' => 9])
            ->assertOk()
            ->assertJsonPath('data.items.0.quantity', 5);

        $this->api()->deleteJson("/api/v1/cart/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.items', [])
            ->assertJsonPath('data.count', 0);

        $this->addToCart($this->customer, $flat);
        $this->api()->deleteJson('/api/v1/cart')->assertNoContent();
        $this->assertSame(0, Cart::where('user_id', $this->customer->id)->count());
    }

    public function test_another_users_cart_line_is_not_found(): void
    {
        $other = $this->makeCustomer();
        $item = $this->addToCart($other, $this->flat());

        $this->api()->patchJson("/api/v1/cart/items/{$item->id}", ['quantity' => 2])->assertNotFound();
        $this->api()->deleteJson("/api/v1/cart/items/{$item->id}")->assertNotFound();

        $this->assertSame(1, $item->refresh()->quantity);
    }

    public function test_cart_and_checkout_need_their_abilities(): void
    {
        $this->api(abilities: ['profile:read'])->getJson('/api/v1/cart')->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->api(abilities: ['cart:write'])->getJson('/api/v1/checkout')->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->getJson('/api/v1/cart')->assertUnauthorized();
    }

    // ── Checkout ─────────────────────────────────────────────────

    public function test_the_checkout_summary_creates_nothing(): void
    {
        $this->api()->getJson('/api/v1/checkout')->assertStatus(422)->assertJsonPath('code', 'business_rule');

        $this->addToCart($this->customer, $this->flat(), 2, 20.0, 5.0);

        $this->api()->getJson('/api/v1/checkout')
            ->assertOk()
            ->assertJsonPath('data.totals.subtotal', 40)
            ->assertJsonPath('data.totals.shipping', 5)
            ->assertJsonPath('data.totals.grand_total', 45)
            ->assertJsonPath('data.shipping.full_name', $this->customer->name)
            ->assertJsonPath('data.payment.gateways', ['stripe', 'payphone'])
            ->assertJsonPath('data.payment.stripe_publishable_key', 'pk_test_123');

        $this->assertSame(0, Order::count());
    }

    public function test_an_order_is_created_with_shipping_and_reused_on_the_next_call(): void
    {
        $this->addToCart($this->customer, $this->flat(), 1, 20.0);

        $first = $this->api()->postJson('/api/v1/checkout/orders', $this->shipping())
            ->assertCreated()
            ->assertJsonPath('data.payment_status', 'unpaid')
            ->assertJsonPath('data.totals.grand_total', 20)
            ->assertJsonPath('data.shipping_address.city', 'Quito')
            ->json('data.id');

        // La dirección queda guardada como en la web.
        $this->assertSame('Av. Amazonas 123', Address::where('user_id', $this->customer->id)->value('address'));

        // El carrito cambia: el mismo pedido se pone al día, no se crea otro.
        $this->addToCart($this->customer, $this->flat(), 1, 10.0);
        $this->api()->postJson('/api/v1/checkout/orders', $this->shipping() + ['order_id' => $first])
            ->assertOk()
            ->assertJsonPath('data.id', $first)
            ->assertJsonPath('data.totals.grand_total', 30)
            ->assertJsonCount(2, 'data.items');

        $this->assertSame(1, Order::count());
    }

    public function test_order_creation_validates_and_never_reuses_someone_elses_order(): void
    {
        $this->addToCart($this->customer, $this->flat());

        $this->api()->postJson('/api/v1/checkout/orders', ['full_name' => 'Ana'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonValidationErrors(['phone', 'email', 'address', 'city']);

        $foreign = $this->makePendingOrder($this->makeCustomer(), $this->seller, 50.0);

        $id = $this->api()->postJson('/api/v1/checkout/orders', $this->shipping() + ['order_id' => $foreign->id])
            ->assertCreated()->json('data.id');

        $this->assertNotSame($foreign->id, $id);
        $this->api()->getJson("/api/v1/checkout/orders/{$foreign->id}")->assertNotFound();
    }

    // ── Stripe ───────────────────────────────────────────────────

    public function test_stripe_returns_a_client_secret_for_the_payment_sheet(): void
    {
        $order = $this->pendingOrder(80);

        $this->mock(StripeService::class, function ($mock) use ($order) {
            $mock->shouldReceive('createIntent')->once()
                ->withArgs(fn (int $cents, string $currency, array $metadata) => $cents === 8000 && $metadata['order_id'] === (string) $order->id)
                ->andReturn(PaymentIntent::constructFrom(['id' => 'pi_app', 'client_secret' => 'cs_app']));
        });

        $this->api()->postJson("/api/v1/checkout/orders/{$order->id}/stripe")
            ->assertOk()
            ->assertJsonPath('data.client_secret', 'cs_app')
            ->assertJsonPath('data.publishable_key', 'pk_test_123')
            ->assertJsonPath('data.amount', 8000)
            ->assertJsonPath('data.order.id', $order->id);

        $this->assertSame('pi_app', $order->refresh()->payment_intent_id);
    }

    public function test_stripe_refuses_a_second_charge_while_one_is_in_progress(): void
    {
        $order = $this->pendingOrder(80);
        $order->update(['payment_intent_id' => 'pi_busy']);

        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldReceive('retrieveIntent')->andReturn(PaymentIntent::constructFrom(['id' => 'pi_busy', 'status' => 'processing']));
            $mock->shouldNotReceive('createIntent');
        });

        $this->api()->postJson("/api/v1/checkout/orders/{$order->id}/stripe")
            ->assertStatus(409)
            ->assertJsonPath('code', 'payment_in_progress');
    }

    public function test_stripe_confirm_marks_the_order_paid_and_empties_the_cart(): void
    {
        $order = $this->pendingOrder(80);
        $order->update(['payment_intent_id' => 'pi_paid']);

        $this->mock(StripeService::class, function ($mock) use ($order) {
            $mock->shouldReceive('retrieveIntent')->with('pi_paid')->andReturn(PaymentIntent::constructFrom([
                'id' => 'pi_paid', 'status' => 'succeeded', 'metadata' => ['order_id' => (string) $order->id],
            ]));
        });

        $this->api()->postJson("/api/v1/checkout/orders/{$order->id}/stripe/confirm")
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.payment_gateway', 'stripe');

        $this->assertSame(0, Cart::where('user_id', $this->customer->id)->count());

        // Un pedido pagado no se vuelve a cobrar.
        $this->api()->postJson("/api/v1/checkout/orders/{$order->id}/stripe")
            ->assertStatus(422)
            ->assertJsonPath('code', 'business_rule');
    }

    // ── PayPhone ─────────────────────────────────────────────────

    protected function fakePayPhone(array $confirm = []): void
    {
        Http::fake([
            self::PREPARE => Http::response([
                'paymentId' => 'pay-1',
                'payWithCard' => 'https://pay.payphonetodoesposible.com/Anonymous/Index?paymentId=pay-1',
                'payWithPayPhone' => 'https://pay.payphonetodoesposible.com/PayPhone/Index?paymentId=pay-1',
            ]),
            self::CONFIRM => Http::response($confirm),
        ]);
    }

    protected function approved(Order $order, string $clientTxId): array
    {
        return [
            'statusCode' => PayPhoneService::STATUS_APPROVED,
            'transactionId' => 555,
            'clientTransactionId' => $clientTxId,
            'amount' => (int) round($order->grand_total * 100),
        ];
    }

    public function test_payphone_start_marks_the_transaction_as_coming_from_the_app(): void
    {
        $this->fakePayPhone();
        $order = $this->pendingOrder(115);

        $txId = $this->api()->postJson("/api/v1/checkout/orders/{$order->id}/payphone", ['method' => 'app'])
            ->assertOk()
            ->assertJsonPath('data.redirect_url', 'https://pay.payphonetodoesposible.com/PayPhone/Index?paymentId=pay-1')
            ->assertJsonPath('data.return_url', 'mitienda://payment-result')
            ->json('data.client_transaction_id');

        $this->assertMatchesRegularExpression('/^GA'.$order->id.'-[A-Z0-9]{10}$/', $txId);

        Http::assertSent(fn (HttpRequest $request) => $request->url() === self::PREPARE
            && $request['clientTransactionId'] === $txId
            && $request['amount'] === 11500
            && $request['cancellationUrl'] === route('payments.payphone.app-cancel'));
    }

    public function test_payphone_return_of_an_app_payment_confirms_and_goes_back_to_the_app(): void
    {
        $order = $this->pendingOrder(115);
        $txId = PayPhoneCheckout::transactionIdFor($order, PayPhoneCheckout::ORIGIN_APP);
        $this->fakePayPhone($this->approved($order, $txId));

        // El navegador in-app no tiene sesión.
        $this->get('/payments/payphone/return?id=555&clientTransactionId='.$txId)
            ->assertRedirect('mitienda://payment-result?status=paid&order='.$order->id);

        $this->assertTrue($order->refresh()->isPaid());
        $this->assertSame(0, Cart::where('user_id', $this->customer->id)->count());

        $this->get('/payments/payphone/app/cancel')->assertRedirect('mitienda://payment-result?status=canceled');
    }

    public function test_payphone_confirm_from_the_api(): void
    {
        $order = $this->pendingOrder(115);
        $txId = PayPhoneCheckout::transactionIdFor($order, PayPhoneCheckout::ORIGIN_APP);
        $this->fakePayPhone($this->approved($order, $txId));

        // Una transacción de otro pedido no sirve para este.
        $other = $this->makePendingOrder($this->customer, $this->seller, 10.0);
        $this->api()->postJson("/api/v1/checkout/orders/{$other->id}/payphone/confirm", ['id' => 555, 'client_transaction_id' => $txId])
            ->assertNotFound();

        $this->api()->postJson("/api/v1/checkout/orders/{$order->id}/payphone/confirm", ['id' => 555, 'client_transaction_id' => $txId])
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.payment_gateway', 'payphone');
    }

    public function test_a_declined_payphone_payment_leaves_the_order_unpaid(): void
    {
        $order = $this->pendingOrder(115);
        $txId = PayPhoneCheckout::transactionIdFor($order, PayPhoneCheckout::ORIGIN_APP);
        $this->fakePayPhone(['statusCode' => PayPhoneService::STATUS_CANCELED]);

        $this->api()->postJson("/api/v1/checkout/orders/{$order->id}/payphone/confirm", ['id' => 555, 'client_transaction_id' => $txId])
            ->assertStatus(422)
            ->assertJsonPath('code', 'business_rule');

        $this->assertFalse($order->refresh()->isPaid());
    }

    public function test_payment_starts_share_the_web_limit_and_answer_in_api_format(): void
    {
        $this->fakePayPhone();
        $order = $this->pendingOrder(115);

        // El contador 'payments' es el mismo que el del checkout web.
        for ($i = 0; $i < 10; $i++) {
            RateLimiter::hit(md5('payments'.'user:'.$this->customer->id));
        }

        $this->api()->postJson("/api/v1/checkout/orders/{$order->id}/payphone")
            ->assertStatus(429)
            ->assertJsonPath('code', 'too_many_requests')
            ->assertHeader('Retry-After');
    }
}
