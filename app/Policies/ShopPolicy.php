<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Shop;
use App\Models\User;

/**
 * La tienda es de su vendedor (shops.user_id). Aprobarla o rechazarla es
 * cosa del panel: solo una cuenta del guard 'admin' (un Admin, no un User)
 * llega a esas reglas, así que un vendedor no puede aprobarse a sí mismo.
 */
class ShopPolicy
{
    public function view(User $user, Shop $shop): bool
    {
        return $user->isAdmin() || $this->owns($user, $shop);
    }

    public function update(User $user, Shop $shop): bool
    {
        return $this->owns($user, $shop);
    }

    // Admite también un User para que Gate::forUser($vendedor) responda
    // false en vez de lanzar un TypeError.
    public function approve(User|Admin $actor, Shop $shop): bool
    {
        return $actor instanceof Admin;
    }

    public function reject(User|Admin $actor, Shop $shop): bool
    {
        return $actor instanceof Admin;
    }

    protected function owns(User $user, Shop $shop): bool
    {
        return (int) $shop->user_id === (int) $user->id;
    }
}
