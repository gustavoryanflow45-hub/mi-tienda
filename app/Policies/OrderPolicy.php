<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Quién ve y quién mueve un pedido.
 *
 * Un pedido mezcla líneas de varios vendedores (order_details.seller_id): un
 * vendedor solo entra si tiene alguna línea en él. Las negativas son 404 y no
 * 403, para no confirmar que existe un pedido ajeno con ese id.
 *
 * Las cuentas del panel (Admin) ven cualquier pedido, pero desde /admin; en
 * la tienda ningún User tiene ese poder.
 */
class OrderPolicy
{
    /** Comprador, vendedor con líneas en el pedido o cuenta del panel. */
    public function view(User|Admin $actor, Order $order): Response
    {
        if ($actor instanceof Admin) {
            return Response::allow();
        }

        return $this->isBuyer($actor, $order) || $this->sellsIn($actor, $order)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /** Confirmar el pedido (Seller\OrderController@confirm). */
    public function confirm(User|Admin $actor, Order $order): Response
    {
        return $actor instanceof User && $this->sellsIn($actor, $order) ? Response::allow() : Response::denyAsNotFound();
    }

    /** Enviarlo al almacén (Seller\OrderController@sendToWarehouse). */
    public function sendToWarehouse(User|Admin $actor, Order $order): Response
    {
        return $actor instanceof User && $this->sellsIn($actor, $order) ? Response::allow() : Response::denyAsNotFound();
    }

    protected function isBuyer(User $user, Order $order): bool
    {
        return (int) $order->user_id === (int) $user->id;
    }

    protected function sellsIn(User $user, Order $order): bool
    {
        if (! $user->isSeller()) {
            return false;
        }

        return $order->relationLoaded('orderDetails')
            ? $order->orderDetails->contains(fn ($detail) => (int) $detail->seller_id === (int) $user->id)
            : $order->orderDetails()->where('seller_id', $user->id)->exists();
    }
}
