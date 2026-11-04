<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\OrderDetail;
use App\Models\User;

/**
 * Una línea de pedido es del vendedor que la vende (seller_id). La ven ese
 * vendedor, el comprador del pedido y las cuentas del panel; solo su
 * vendedor la mueve.
 */
class OrderDetailPolicy
{
    public function view(User|Admin $actor, OrderDetail $detail): bool
    {
        return $actor instanceof Admin
            || $this->sells($actor, $detail)
            || (int) $detail->order?->user_id === (int) $actor->id;
    }

    /** Cambiar su estado de entrega (confirmar, enviar al almacén). */
    public function update(User|Admin $actor, OrderDetail $detail): bool
    {
        return $actor instanceof User && $this->sells($actor, $detail);
    }

    protected function sells(User $user, OrderDetail $detail): bool
    {
        return $detail->seller_id !== null && (int) $detail->seller_id === (int) $user->id;
    }
}
