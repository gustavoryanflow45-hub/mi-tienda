<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

/**
 * Antes del commit 34a2156 el checkout guardaba el envío en las líneas sin
 * sumarlo al pedido: el pedido se cobró sin envío, pero sus líneas lo llevan.
 */
class OrderShippingTotalsTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    /** Pedido de 60 + envío; $chargedShipping es lo que se sumó al pedido. */
    protected function makeOrder(User $customer, User $seller, float $lineShipping, float $chargedShipping, string $paymentStatus = 'paid'): Order
    {
        $product = $this->makeProduct($seller, 60);

        $order = Order::create([
            'user_id' => $customer->id,
            'code' => 'ORD-TEST-'.strtoupper(uniqid()),
            'status' => 'pendiente',
            'payment_status' => $paymentStatus,
            'delivery_status' => 'pending',
            'subtotal' => 60,
            'shipping_total' => $chargedShipping,
            'tax_amount' => 0,
            'grand_total' => 60 + $chargedShipping,
            'shipping_address' => $this->completeShippingAddress($customer),
        ]);

        $order->orderDetails()->create([
            'seller_id' => $seller->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 60,
            'quantity' => 1,
            'shipping_cost' => $lineShipping,
            'payment_status' => $paymentStatus,
        ]);

        return $order;
    }

    /** Importe que la caja "Importe del pedido" muestra en la fila $label. */
    protected function amountRow(string $content, string $label): string
    {
        $box = substr($content, strpos($content, 'Importe del pedido'));
        $this->assertSame(1, preg_match('/'.preg_quote($label, '/').'<\/span>\s*<span[^>]*>(-?\$[\d.,]+)/u', $box, $m), "Sin fila $label");

        return $m[1];
    }

    public function test_the_amount_box_shows_what_was_charged_not_the_line_sum(): void
    {
        $customer = $this->makeCustomer();
        $seller = $this->makeSeller();

        // Pedido viejo: la línea lleva 7 de envío que no se cobró
        $old = $this->makeOrder($customer, $seller, lineShipping: 7, chargedShipping: 0);
        $content = $this->actingAs($customer)->get(route('orders.show', $old->id))->assertOk()->getContent();
        $this->assertSame('$0.00', $this->amountRow($content, 'Envío'));
        $this->assertSame('$60.00', $this->amountRow($content, 'TOTAL'));

        // Pedido actual: el envío se cobró y sale igual que antes
        $new = $this->makeOrder($customer, $seller, lineShipping: 7, chargedShipping: 7);
        $content = $this->actingAs($customer)->get(route('orders.show', $new->id))->assertOk()->getContent();
        $this->assertSame('$7.00', $this->amountRow($content, 'Envío'));
        $this->assertSame('$67.00', $this->amountRow($content, 'TOTAL'));
    }

    public function test_the_migration_clears_only_uncharged_shipping_on_unpaid_orders(): void
    {
        $customer = $this->makeCustomer();
        $seller = $this->makeSeller();

        $oldUnpaid = $this->makeOrder($customer, $seller, 7, 0, 'unpaid');
        $oldPaid = $this->makeOrder($customer, $seller, 7, 0, 'paid');
        $currentUnpaid = $this->makeOrder($customer, $seller, 7, 7, 'unpaid');

        (require database_path('migrations/2026_10_17_120000_clear_uncharged_shipping_on_unpaid_orders.php'))->up();

        $this->assertEquals(0, $oldUnpaid->orderDetails()->value('shipping_cost'));
        // Pagado: queda tal cual, su liquidación (si la hubo) cuadra con la línea
        $this->assertEquals(7, $oldPaid->orderDetails()->value('shipping_cost'));
        // Checkout actual: el envío sí está en el pedido
        $this->assertEquals(7, $currentUnpaid->orderDetails()->value('shipping_cost'));
    }
}
