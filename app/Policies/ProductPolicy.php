<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Un vendedor solo gestiona sus propios productos (products.added_by); el
 * admin, todos. SellerProductController conserva además su comprobación de
 * rol en el constructor como defensa en profundidad.
 */
class ProductPolicy
{
    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin() || $this->owns($user, $product);
    }

    /** Publicar / despublicar (HomeController@togglePublished). */
    public function togglePublished(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }

    protected function owns(User $user, Product $product): bool
    {
        return $product->added_by !== null && (int) $product->added_by === (int) $user->id;
    }
}
