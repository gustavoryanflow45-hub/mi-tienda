<?php

namespace Tests\Feature;

use App\Exceptions\BusinessRuleException;
use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use App\Models\WalletRecharge;
use App\Models\WalletWithdrawal;
use App\Services\OrderFulfillmentService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCheckoutData;
use Tests\Concerns\PassesAdminTwoFactor;
use Tests\TestCase;

/**
 * Pedidos de cualquier vendedor en /admin/orders (solo lectura) y las
 * reglas de los servicios compartidos que mueven pedidos y saldo.
 */
class AdminOrdersTest extends TestCase
{
    use BuildsCheckoutData;
    use PassesAdminTwoFactor;
    use RefreshDatabase;

    protected function makePaidOrder(User $customer, User $seller): Order
    {
        $order = $this->makePendingOrder($customer, $seller);
        $order->markPaid('stripe', 'pi_admin_orders');

        return $order->fresh();
    }

    public function test_the_panel_lists_and_shows_any_order_with_buyer_data(): void
    {
        $customer = $this->makeCustomer();
        $seller = $this->makeSeller();
        $order = $this->makePaidOrder($customer, $seller);
        $admin = Admin::factory()->create();

        $this->actingAsAdmin($admin)->get('/admin/orders?payment_status=paid')
            ->assertOk()
            ->assertSee($order->code);

        $this->actingAsAdmin($admin)->get("/admin/orders/{$order->id}")
            ->assertOk()
            ->assertSee($order->code)
            ->assertSee($order->shippingInfo()['phone'])
            ->assertSee($seller->shop->name);
    }

    public function test_filters_narrow_the_list(): void
    {
        $paid = $this->makePaidOrder($this->makeCustomer(), $this->makeSeller());
        $unpaid = $this->makePendingOrder($this->makeCustomer(), $this->makeSeller());

        $this->actingAsAdmin(Admin::factory()->create())->get('/admin/orders?payment_status=unpaid')
            ->assertOk()
            ->assertSee($unpaid->code)
            ->assertDontSee($paid->code);
    }

    public function test_the_storefront_order_page_no_longer_serves_staff(): void
    {
        $order = $this->makePaidOrder($this->makeCustomer(), $this->makeSeller());

        // Ni un vendedor ajeno ni una fila vieja de admin: 404, como antes a cualquier ajeno.
        $this->actingAs($this->makeSeller())->get("/orders/{$order->id}")->assertNotFound();
        $this->actingAs(User::factory()->create(['user_type' => 'admin']))->get("/orders/{$order->id}")->assertNotFound();
    }

    // ── Servicios ────────────────────────────────────────────────

    public function test_a_recharge_is_credited_once_even_if_approved_twice(): void
    {
        $customer = $this->makeCustomer();
        $recharge = WalletRecharge::create([
            'user_id' => $customer->id, 'amount' => 40, 'network' => 'TRC20',
            'transaction_id' => 'tx-1', 'approval' => 0,
        ]);
        $wallet = app(WalletService::class);
        $admin = Admin::factory()->create();

        $wallet->approveRecharge($recharge, $admin);

        try {
            // El mismo objeto, aún con approval = 0 en memoria: la fila se relee bloqueada.
            $wallet->approveRecharge($recharge, $admin);
            $this->fail('La segunda aprobación debía rechazarse.');
        } catch (BusinessRuleException) {
        }

        $this->assertEquals(40.0, (float) $customer->fresh()->balance);
    }

    public function test_a_withdrawal_is_refunded_once_even_if_rejected_twice(): void
    {
        $customer = $this->makeCustomer();
        $withdrawal = WalletWithdrawal::create([
            'user_id' => $customer->id, 'amount' => 25, 'full_name' => 'X', 'bank_name' => 'B',
            'account_number' => '1', 'status' => 'pending',
        ]);
        $wallet = app(WalletService::class);
        $admin = Admin::factory()->create();

        $wallet->rejectWithdrawal($withdrawal, $admin);
        $this->expectException(BusinessRuleException::class);

        try {
            $wallet->rejectWithdrawal($withdrawal, $admin);
        } finally {
            $this->assertEquals(25.0, (float) $customer->fresh()->balance);
        }
    }

    public function test_fulfillment_steps_require_the_previous_one(): void
    {
        $seller = $this->makeSeller();
        $order = $this->makePaidOrder($this->makeCustomer(), $seller);
        $fulfillment = app(OrderFulfillmentService::class);
        $admin = Admin::factory()->create();

        try {
            $fulfillment->dispatch($order, $admin);
            $this->fail('No se puede despachar un pedido que no está en el almacén.');
        } catch (BusinessRuleException $e) {
            $this->assertSame(__('Solo se pueden despachar pedidos que están en el almacén.'), $e->getMessage());
        }

        $fulfillment->confirm($order, $seller);
        $fulfillment->sendToWarehouse($order, $seller);
        $fulfillment->dispatch($order, $admin);
        $fulfillment->deliver($order, $admin);

        $order->refresh();
        $this->assertSame('delivered', $order->delivery_status);
        $this->assertSame($admin->id, (int) $order->dispatched_by);
        $this->assertSame(['delivered'], $order->orderDetails()->pluck('delivery_status')->unique()->values()->all());

        $this->expectException(BusinessRuleException::class);
        $fulfillment->deliver($order, $admin);
    }
}
