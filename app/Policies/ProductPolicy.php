<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Product;
use App\Models\User;

/**
 * Un vendedor solo gestiona sus propios productos (products.added_by). Una
 * cuenta del panel (Admin, guard 'admin') los modera todos desde /admin;
 * ningún User de la tienda tiene ese poder, sea cual sea su user_type.
 */
class ProductPolicy
{
    public function update(User|Admin $actor, Product $product): bool
    {
        return $actor instanceof Admin || $this->owns($actor, $product);
    }

    /** Publicar / despublicar (Seller\ProductController@togglePublished): solo su vendedor. */
    public function togglePublished(User|Admin $actor, Product $product): bool
    {
        return $actor instanceof User && $this->owns($actor, $product);
    }

    /** Aprobar / desactivar (products.approved): solo el panel. */
    public function moderate(User|Admin $actor, Product $product): bool
    {
        return $actor instanceof Admin;
    }

    protected function owns(User $user, Product $product): bool
    {
        return $product->added_by !== null && (int) $product->added_by === (int) $user->id;
    }
}
