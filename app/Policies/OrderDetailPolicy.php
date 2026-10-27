<?php

namespace App\Policies;

use App\Models\OrderDetail;
use App\Models\User;

/**
 * Una línea de pedido es del vendedor que la vende (seller_id). La ven ese
 * vendedor, el comprador del pedido y el admin; solo su vendedor la mueve.
 */
class OrderDetailPolicy
{
    public function view(User $user, OrderDetail $detail): bool
    {
        return $user->isAdmin()
            || $this->sells($user, $detail)
            || (int) $detail->order?->user_id === (int) $user->id;
    }

    /** Cambiar su estado de entrega (confirmar, enviar al almacén). */
    public function update(User $user, OrderDetail $detail): bool
    {
        return $this->sells($user, $detail);
    }

    protected function sells(User $user, OrderDetail $detail): bool
    {
        return $detail->seller_id !== null && (int) $detail->seller_id === (int) $user->id;
    }
}
