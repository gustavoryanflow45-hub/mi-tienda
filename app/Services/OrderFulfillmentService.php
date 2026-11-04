<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderArrivedWarehouseNotification;
use App\Notifications\OrderStatusUpdatedNotification;
use Illuminate\Support\Facades\DB;

/**
 * El camino de un pedido pagado hasta el cliente:
 *
 *   pending → confirmed (vendedor) → warehouse (vendedor)
 *           → on_the_way (almacén) → delivered (almacén)
 *
 * Cada paso exige el anterior, sella su fecha y avisa al comprador. Los
 * pasos del vendedor solo mueven sus líneas; los del almacén, todas. La
 * entrega es lo que vuelve liquidable una venta (OrderDetail::scopeSettleable).
 *
 * Quién puede llamar a cada paso lo deciden las rutas y OrderPolicy; aquí
 * solo se valida el estado. Cada transición bloquea el pedido y vuelve a
 * leer su estado, así que dos clics no la aplican dos veces.
 */
class OrderFulfillmentService
{
    public function __construct(private AuditLog $audit) {}

    /** Vendedor: confirma un pedido pagado y pendiente. */
    public function confirm(Order $order, User $seller): Order
    {
        $order = $this->transition($order, function (Order $order) use ($seller) {
            if ($order->payment_status !== 'paid') {
                throw new BusinessRuleException(__('El pedido aún no tiene el pago confirmado.'));
            }

            if ($order->delivery_status !== 'pending') {
                throw new BusinessRuleException(__('Solo se pueden confirmar pedidos en estado "Pendiente".'));
            }

            $order->update(['delivery_status' => 'confirmed', 'confirmed_at' => now()]);
            $order->orderDetails()->where('seller_id', $seller->id)->update(['delivery_status' => 'confirmed']);
        });

        $order->user?->notify(new OrderStatusUpdatedNotification($order, 'confirmed'));

        return $order;
    }

    /**
     * Vendedor: entrega al almacén un pedido confirmado. El almacén no puede
     * despachar sin los datos del comprador: si faltan, intenta completarlos
     * con su dirección guardada antes de bloquear.
     */
    public function sendToWarehouse(Order $order, User $seller): Order
    {
        $order = $this->transition($order, function (Order $order) use ($seller) {
            if ($order->delivery_status !== 'confirmed') {
                throw new BusinessRuleException(__('El pedido debe estar en estado "Confirmado" para enviarlo al almacén.'));
            }

            if (! $order->hasCompleteShippingInfo() && $order->user) {
                $order->update(['shipping_address' => array_merge(
                    $order->user->shippingSnapshot(),
                    array_filter($order->shippingInfo(), fn ($v) => trim((string) $v) !== ''),
                )]);
            }

            if (! $order->hasCompleteShippingInfo()) {
                throw new BusinessRuleException(__('No se puede enviar al almacén: faltan datos de envío del cliente (nombre, teléfono, correo, dirección o ciudad). Pide al cliente que complete su dirección de envío.'));
            }

            $order->update(['delivery_status' => 'warehouse', 'warehouse_at' => now()]);
            $order->orderDetails()->where('seller_id', $seller->id)->update(['delivery_status' => 'warehouse']);
        });

        // Al personal del almacén (todas las cuentas del panel) y al cliente.
        Admin::all()->each(fn (Admin $admin) => $admin->notify(new OrderArrivedWarehouseNotification($order, $seller->name)));
        $order->user?->notify(new OrderStatusUpdatedNotification($order, 'warehouse'));

        return $order;
    }

    /** Almacén: despacha un pedido que está en el almacén. */
    public function dispatch(Order $order, Admin $admin): Order
    {
        $order = $this->transition($order, function (Order $order) use ($admin) {
            if ($order->delivery_status !== 'warehouse') {
                throw new BusinessRuleException(__('Solo se pueden despachar pedidos que están en el almacén.'));
            }

            $order->update([
                'delivery_status' => 'on_the_way',
                'dispatched_at' => now(),
                'dispatched_by' => $admin->id,
            ]);
            $order->orderDetails()->update(['delivery_status' => 'on_the_way']);
        });

        $this->audit->record('order.dispatched', $order, [], $admin);
        $order->user?->notify(new OrderStatusUpdatedNotification($order, 'on_the_way'));

        return $order;
    }

    /** Almacén: marca entregado un pedido en camino (y lo vuelve liquidable). */
    public function deliver(Order $order, Admin $admin): Order
    {
        $order = $this->transition($order, function (Order $order) {
            if ($order->delivery_status !== 'on_the_way') {
                throw new BusinessRuleException(__('Solo se pueden marcar como entregados los pedidos que están en camino.'));
            }

            $order->update(['delivery_status' => 'delivered', 'delivered_at' => now()]);
            $order->orderDetails()->update(['delivery_status' => 'delivered']);
        });

        $this->audit->record('order.delivered', $order, [], $admin);
        $order->user?->notify(new OrderStatusUpdatedNotification($order, 'delivered'));

        return $order;
    }

    /**
     * Aplica el paso con el pedido bloqueado y releído. Los avisos se mandan
     * después del commit: un correo fallido no deshace un cambio ya hecho.
     */
    protected function transition(Order $order, callable $step): Order
    {
        return DB::transaction(function () use ($order, $step) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $step($locked);

            return $locked;
        });
    }
}
