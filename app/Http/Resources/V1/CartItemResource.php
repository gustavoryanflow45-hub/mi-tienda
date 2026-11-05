<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Línea del carrito. `price` es el precio unitario fijado al añadirla (con
 * el descuento ya aplicado); `variation` es la clave de la combinación, la
 * misma de `variants.combinations` en la ficha, y `variant` su versión
 * legible. Espera product.category cargado (CartService::items()).
 *
 * @mixin \App\Models\Cart
 */
class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->product;

        return [
            'id' => $this->id,
            'product' => $product ? [
                'id' => $product->id,
                'slug' => $product->slug,
                'name' => $product->name,
                'thumbnail_url' => uploaded_asset($product->thumbnail),
                // false: despublicado o vetado después de añadirlo. Sigue en el
                // carrito (como en la web), pero ya no se puede volver a añadir.
                'on_sale' => (int) $product->published === 1 && (int) $product->approved === 1,
            ] : null,
            'variation' => (string) $this->variation,
            'variant' => self::variant($this->resource),
            'price' => round((float) $this->price, 2),
            'quantity' => (int) $this->quantity,
            'line_total' => round((float) $this->price * $this->quantity, 2),
            'shipping_cost' => round((float) $this->shipping_cost, 2),
            // Unidades que quedan de esta combinación; tope para el selector de cantidad.
            'available_qty' => (int) ($this->available_stock ?? 0),
        ];
    }

    /**
     * Talla y color legibles de una línea (carrito o pedido), o null si no
     * tiene variación. Mismas partes que pinta partials/variant-badge.
     */
    public static function variant($line): ?array
    {
        if (! $line->hasVariant()) {
            return null;
        }

        $parts = $line->variant_parts;

        return [
            'text' => $parts['text'],
            'size' => $parts['size'],
            'size_label' => $parts['size'] !== null ? $parts['size_label'] : null,
            'color' => $parts['color'],
            'color_label' => $parts['color_label'],
            'color_hex' => $parts['color_hex'],
        ];
    }
}
