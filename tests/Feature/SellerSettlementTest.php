<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\SellerSettlement;
use App\Models\User;
use App\Notifications\SellerSettledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

/**
 * Ganancias del vendedor = ventas − comisión del marketplace, contando solo
 * pedidos cobrados y entregados; solo el admin puede liquidarlas y, al
 * hacerlo, el neto se acredita en la billetera del vendedor y ventas y
 * ganancias vuelven a cero.
 */
class SellerSettlementTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.seller_commission_rate' => 0.25]);
        Notification::fake();
    }

    protected function makeAdmin(): User
    {
        return User::factory()->create(['user_type' => 'admin']);
    }

    protected function makeVerifiedSeller(): User
    {
        $seller = $this->makeSeller();
        $seller->forceFill(['email_verified_at' => now()])->save();

        return $seller;
    }

    /** Cobrado pero todavía en camino: no cuenta ni se puede liquidar. */
    protected function makePaidOrder(User $customer, User $seller, float $total): Order
    {
        $order = $this->makePendingOrder($customer, $seller, $total);
        $order->markPaid('stripe', 'pi_settlement_'.uniqid());

        return $order->fresh();
    }

    /** Cobrado y entregado, como lo deja WarehouseController@deliver. */
    protected function makeDeliveredOrder(User $customer, User $seller, float $total): Order
    {
        $order = $this->makePaidOrder($customer, $seller, $total);
        $order->update(['delivery_status' => 'delivered', 'delivered_at' => now()]);
        $order->orderDetails()->update(['delivery_status' => 'delivered']);

        return $order->fresh();
    }

    public function test_dashboard_profits_are_sales_minus_commission(): void
    {
        $seller = $this->makeVerifiedSeller();
        $customer = $this->makeCustomer();

        $this->makeDeliveredOrder($customer, $seller, 200.00);
        // Ni un pedido sin pagar ni uno pagado pero aún no entregado cuentan.
        $this->makePendingOrder($customer, $seller, 999.00);
        $this->makePaidOrder($customer, $seller, 555.00);

        $response = $this->actingAs($seller)->get('/dashboard');

        $response->assertOk();
        $response->assertViewHas('stats', fn ($stats) => (float) $stats['total_sale'] === 200.00
            && (float) $stats['total_profits'] === 150.00
            && (float) $stats['commission_rate'] === 0.25);
        $response->assertSee('$200.00');
        $response->assertSee('$150.00');
    }

    public function test_only_admin_can_open_or_run_settlements(): void
    {
        $seller = $this->makeVerifiedSeller();

        // Invitado primero: actingAs deja al usuario logueado el resto del test.
        $this->get('/admin/settlements')->assertRedirect('/login');

        $this->actingAs($this->makeCustomer())->get('/admin/settlements')->assertForbidden();
        $this->actingAs($seller)->get('/admin/settlements')->assertForbidden();
        $this->actingAs($seller)->post('/admin/settlements/'.$seller->id)->assertForbidden();

        $this->assertDatabaseCount('seller_settlements', 0);
    }

    public function test_admin_panel_lists_pending_amounts_per_seller(): void
    {
        $admin = $this->makeAdmin();
        $seller = $this->makeVerifiedSeller();
        $other = $this->makeVerifiedSeller();
        $customer = $this->makeCustomer();

        $this->makeDeliveredOrder($customer, $seller, 100.00);
        $this->makeDeliveredOrder($customer, $seller, 60.00);
        $this->makeDeliveredOrder($customer, $other, 40.00);

        $response = $this->actingAs($admin)->get('/admin/settlements');

        $response->assertOk();
        $response->assertViewHas('rows', function ($rows) use ($seller, $other) {
            $bySeller = $rows->keyBy(fn ($row) => $row['seller']->id);

            return (float) $bySeller[$seller->id]['pending']['total_sales'] === 160.00
                && (float) $bySeller[$seller->id]['pending']['commission'] === 40.00
                && (float) $bySeller[$seller->id]['pending']['net_amount'] === 120.00
                && $bySeller[$seller->id]['pending']['lines_count'] === 2
                && (float) $bySeller[$other->id]['pending']['net_amount'] === 30.00;
        });
        $response->assertViewHas('totals', fn ($totals) => (float) $totals['net_amount'] === 150.00
            && $totals['sellers_pending'] === 2);
    }

    public function test_settling_resets_the_seller_dashboard_and_records_the_payout(): void
    {
        $admin = $this->makeAdmin();
        $seller = $this->makeVerifiedSeller();
        $other = $this->makeVerifiedSeller();
        $customer = $this->makeCustomer();

        $this->makeDeliveredOrder($customer, $seller, 100.00);
        $this->makeDeliveredOrder($customer, $seller, 60.00);
        $this->makeDeliveredOrder($customer, $other, 40.00);

        $this->actingAs($admin)
            ->post('/admin/settlements/'.$seller->id)
            ->assertRedirect()
            ->assertSessionHas('success');

        $settlement = SellerSettlement::sole();
        $this->assertSame($seller->id, $settlement->seller_id);
        $this->assertSame($admin->id, $settlement->admin_id);
        $this->assertSame('160.00', $settlement->total_sales);
        $this->assertSame('40.00', $settlement->commission);
        $this->assertSame('120.00', $settlement->net_amount);
        $this->assertSame(2, $settlement->lines_count);

        // Las líneas del vendedor quedan ligadas a la liquidación; las del otro, no.
        $this->assertSame(2, $settlement->orderDetails()->count());
        $this->assertDatabaseHas('order_details', ['seller_id' => $other->id, 'settlement_id' => null]);

        // El neto se acredita en la billetera del vendedor, no en la del otro.
        $this->assertSame('120.00', $seller->fresh()->balance);
        $this->assertSame('0.00', $other->fresh()->balance);

        // Y se le avisa por base de datos y correo.
        Notification::assertSentTo($seller, SellerSettledNotification::class, function ($notification, $channels) use ($settlement) {
            return $notification->settlement->is($settlement)
                && in_array('database', $channels, true)
                && in_array('mail', $channels, true);
        });
        Notification::assertNotSentTo($other, SellerSettledNotification::class);

        // El panel del vendedor vuelve a cero...
        $this->actingAs($seller)->get('/dashboard')
            ->assertOk()
            ->assertViewHas('stats', fn ($stats) => (float) $stats['total_sale'] === 0.0
                && (float) $stats['total_profits'] === 0.0)
            ->assertViewHas('lastSettlement', fn ($last) => $last?->id === $settlement->id);

        // ...y el del otro vendedor no se toca.
        $this->actingAs($other)->get('/dashboard')
            ->assertViewHas('stats', fn ($stats) => (float) $stats['total_profits'] === 30.00);

        // Una venta posterior empieza el ciclo nuevo desde cero.
        $this->makeDeliveredOrder($customer, $seller, 20.00);
        $this->actingAs($seller)->get('/dashboard')
            ->assertViewHas('stats', fn ($stats) => (float) $stats['total_sale'] === 20.00
                && (float) $stats['total_profits'] === 15.00);
    }

    public function test_paid_but_undelivered_sales_cannot_be_settled(): void
    {
        $admin = $this->makeAdmin();
        $seller = $this->makeVerifiedSeller();
        $customer = $this->makeCustomer();

        $order = $this->makePaidOrder($customer, $seller, 100.00);

        $this->actingAs($admin)->get('/admin/settlements')
            ->assertViewHas('totals', fn ($totals) => (float) $totals['net_amount'] === 0.0
                && $totals['sellers_pending'] === 0);

        $this->actingAs($admin)
            ->post('/admin/settlements/'.$seller->id)
            ->assertSessionHas('warning');
        $this->assertDatabaseCount('seller_settlements', 0);

        // En cuanto el almacén la entrega, pasa a ser liquidable.
        $order->update(['delivery_status' => 'delivered', 'delivered_at' => now()]);
        $order->orderDetails()->update(['delivery_status' => 'delivered']);

        $this->actingAs($admin)
            ->post('/admin/settlements/'.$seller->id)
            ->assertSessionHas('success');
        $this->assertDatabaseCount('seller_settlements', 1);
        $this->assertSame('75.00', SellerSettlement::sole()->net_amount);
    }

    public function test_seller_wallet_lists_credited_settlements(): void
    {
        $admin = $this->makeAdmin();
        $seller = $this->makeVerifiedSeller();
        $customer = $this->makeCustomer();

        $this->makeDeliveredOrder($customer, $seller, 100.00);
        $this->actingAs($admin)->post('/admin/settlements/'.$seller->id)->assertSessionHas('success');

        $this->actingAs($seller)->get('/wallet')
            ->assertOk()
            ->assertSee('Liquidaciones de ventas acreditadas')
            ->assertSee('$75.00');

        // Un cliente sin liquidaciones no ve la tabla.
        $this->actingAs($customer)->get('/wallet')
            ->assertOk()
            ->assertDontSee('Liquidaciones de ventas acreditadas');
    }

    public function test_settling_with_nothing_pending_records_nothing(): void
    {
        $admin = $this->makeAdmin();
        $seller = $this->makeVerifiedSeller();

        $this->actingAs($admin)
            ->post('/admin/settlements/'.$seller->id)
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertDatabaseCount('seller_settlements', 0);
    }

    public function test_settling_twice_does_not_pay_the_same_sales_again(): void
    {
        $admin = $this->makeAdmin();
        $seller = $this->makeVerifiedSeller();
        $customer = $this->makeCustomer();

        $this->makeDeliveredOrder($customer, $seller, 100.00);

        $this->actingAs($admin)->post('/admin/settlements/'.$seller->id)->assertSessionHas('success');
        $this->actingAs($admin)->post('/admin/settlements/'.$seller->id)->assertSessionHas('warning');

        $this->assertDatabaseCount('seller_settlements', 1);
        $this->assertEqualsWithDelta(75.0, (float) SellerSettlement::sum('net_amount'), 0.001);
        $this->assertSame('75.00', $seller->fresh()->balance);
    }
}
