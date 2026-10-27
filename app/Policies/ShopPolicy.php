<?php

namespace App\Policies;

use App\Models\Shop;
use App\Models\User;

/**
 * La tienda es de su vendedor (shops.user_id). Aprobarla o rechazarla es
 * cosa del admin: un vendedor no puede aprobarse a sí mismo.
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

    public function approve(User $user, Shop $shop): bool
    {
        return $user->isAdmin();
    }

    public function reject(User $user, Shop $shop): bool
    {
        return $user->isAdmin();
    }

    protected function owns(User $user, Shop $shop): bool
    {
        return (int) $shop->user_id === (int) $user->id;
    }
}
