<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Quién ve y quién mueve un pedido.
 *
 * Un pedido mezcla líneas de varios vendedores (order_details.seller_id): un
 * vendedor solo entra si tiene alguna línea en él. Las negativas son 404 y no
 * 403, para no confirmar que existe un pedido ajeno con ese id.
 */
class OrderPolicy
{
    /** Comprador, vendedor con líneas en el pedido o admin (almacén). */
    public function view(User $user, Order $order): Response
    {
        return $user->isAdmin() || $this->isBuyer($user, $order) || $this->sellsIn($user, $order)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /** Confirmar el pedido (SellerOrderController@confirm). */
    public function confirm(User $user, Order $order): Response
    {
        return $this->sellsIn($user, $order) ? Response::allow() : Response::denyAsNotFound();
    }

    /** Enviarlo al almacén (OrderController@sendToWarehouse). */
    public function sendToWarehouse(User $user, Order $order): Response
    {
        return $this->sellsIn($user, $order) ? Response::allow() : Response::denyAsNotFound();
    }

    protected function isBuyer(User $user, Order $order): bool
    {
        return (int) $order->user_id === (int) $user->id;
    }

    protected function sellsIn(User $user, Order $order): bool
    {
        if (! in_array($user->user_type, ['seller', 'admin'], true)) {
            return false;
        }

        return $order->relationLoaded('orderDetails')
            ? $order->orderDetails->contains(fn ($detail) => (int) $detail->seller_id === (int) $user->id)
            : $order->orderDetails()->where('seller_id', $user->id)->exists();
    }
}
