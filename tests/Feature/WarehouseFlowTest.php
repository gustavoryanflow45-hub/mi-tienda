<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderArrivedWarehouseNotification;
use App\Notifications\OrderStatusUpdatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

class WarehouseFlowTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        return User::factory()->create(['user_type' => 'admin']);
    }

    /** Order already paid, ready to start the delivery flow. */
    protected function makePaidOrder(User $customer, User $seller): Order
    {
        $order = $this->makePendingOrder($customer, $seller);
        $order->markPaid('stripe', 'pi_warehouse_test');

        return $order->fresh();
    }

    public function test_customer_cannot_access_warehouse_panel(): void
    {
        $this->actingAs($this->makeCustomer())
            ->get('/warehouse')
            ->assertForbidden();
    }

    public function test_full_flow_confirm_warehouse_dispatch_deliver(): void
    {
        $customer = $this->makeCustomer();
        $seller = $this->makeSeller();
        $admin = $this->makeAdmin();
        $order = $this->makePaidOrder($customer, $seller);

        // 1. Vendedor confirma el pedido
        $this->actingAs($seller)
            ->post("/seller/orders/{$order->id}/confirm")
            ->assertSessionHas('warehouse_success');

        $order->refresh();
        $this->assertSame('confirmed', $order->delivery_status);
        $this->assertNotNull($order->confirmed_at);

        // 2. Vendedor envía al almacén → se notifica al admin y al cliente
        $this->actingAs($seller)
            ->post("/orders/{$order->id}/send-to-warehouse")
            ->assertSessionHas('warehouse_success');

        $order->refresh();
        $this->assertSame('warehouse', $order->delivery_status);
        $this->assertNotNull($order->warehouse_at);

        $this->assertTrue(
            $admin->notifications()->where('type', OrderArrivedWarehouseNotification::class)->exists(),
            'El admin debe recibir la notificación de llegada al almacén.'
        );

        // 3. Panel de almacén muestra el pedido y la llegada nueva
        $this->actingAs($admin)
            ->get('/warehouse')
            ->assertOk()
            ->assertSee($order->code);

        // La llegada queda marcada como leída al abrir el panel
        $this->assertSame(
            0,
            $admin->unreadNotifications()->where('type', OrderArrivedWarehouseNotification::class)->count()
        );

        // 4. Almacén despacha → en camino
        $this->actingAs($admin)
            ->post("/warehouse/orders/{$order->id}/dispatch")
            ->assertSessionHas('warehouse_success');

        $order->refresh();
        $this->assertSame('on_the_way', $order->delivery_status);
        $this->assertNotNull($order->dispatched_at);
        $this->assertSame($admin->id, $order->dispatched_by);
        $this->assertSame('on_the_way', $order->orderDetails()->first()->delivery_status);

        // 5. Almacén marca entregado
        $this->actingAs($admin)
            ->post("/warehouse/orders/{$order->id}/deliver")
            ->assertSessionHas('warehouse_success');

        $order->refresh();
        $this->assertSame('delivered', $order->delivery_status);
        $this->assertNotNull($order->delivered_at);

        // 6. El cliente recibió avisos por cada cambio de estado
        $statuses = $customer->notifications()
            ->where('type', OrderStatusUpdatedNotification::class)
            ->get()
            ->pluck('data.status')
            ->all();

        $this->assertEqualsCanonicalizing(
            ['confirmed', 'warehouse', 'on_the_way', 'delivered'],
            $statuses
        );

        // 7. El cliente ve el tracker con su pedido entregado
        $this->actingAs($customer)
            ->get("/orders/{$order->id}")
            ->assertOk()
            ->assertSee('Entregado');
    }

    public function test_dispatch_requires_warehouse_status(): void
    {
        $order = $this->makePaidOrder($this->makeCustomer(), $this->makeSeller());
        $admin = $this->makeAdmin();

        // Aún está "pending": no se puede despachar
        $this->actingAs($admin)
            ->post("/warehouse/orders/{$order->id}/dispatch")
            ->assertSessionHas('warehouse_error');

        $this->assertSame('pending', $order->fresh()->delivery_status);
    }

    public function test_deliver_requires_on_the_way_status(): void
    {
        $order = $this->makePaidOrder($this->makeCustomer(), $this->makeSeller());
        $order->update(['delivery_status' => 'warehouse', 'warehouse_at' => now()]);
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post("/warehouse/orders/{$order->id}/deliver")
            ->assertSessionHas('warehouse_error');

        $this->assertSame('warehouse', $order->fresh()->delivery_status);
    }

    public function test_send_to_warehouse_requires_confirmed_status(): void
    {
        $seller = $this->makeSeller();
        $order = $this->makePaidOrder($this->makeCustomer(), $seller);

        $this->actingAs($seller)
            ->post("/orders/{$order->id}/send-to-warehouse")
            ->assertSessionHas('warehouse_error');

        $this->assertSame('pending', $order->fresh()->delivery_status);
    }

    public function test_customer_sees_status_updates_banner_in_orders_page(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makePaidOrder($customer, $this->makeSeller());

        $customer->notify(new OrderStatusUpdatedNotification($order, 'on_the_way'));

        $this->actingAs($customer)
            ->get('/orders')
            ->assertOk()
            ->assertSee('va en camino');

        // Se marcan como leídas al verlas
        $this->assertSame(0, $customer->unreadNotifications()->count());
    }
}
