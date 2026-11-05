<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tarjeta de producto (listados). Precios en la moneda de la tienda, con
 * dos decimales: `price` es lo que se cobra (Product::finalPrice(), con el
 * descuento aplicado) y `original_price` el de antes del descuento.
 *
 * Espera category, brand y stocks cargados (CatalogService::products()).
 *
 * @mixin \App\Models\Product
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hasDiscount = $this->discount > 0;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'thumbnail_url' => uploaded_asset($this->thumbnail),
            'price' => $this->finalPrice(),
            'original_price' => round((float) $this->unit_price, 2),
            'discount' => $hasDiscount ? [
                'type' => $this->discount_type === 'percent' ? 'percent' : 'amount',
                'value' => round((float) $this->discount, 2),
            ] : null,
            'unit' => $this->unit,
            'rating' => round((float) $this->rating, 2),
            'in_stock' => $this->stocks->sum('qty') > 0,
            // true = hay que elegir talla y/o color antes de añadir al carrito.
            'has_variants' => $this->hasVariants(),
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null,
            'brand' => $this->brand ? ['id' => $this->brand->id, 'name' => $this->brand->name] : null,
        ];
    }
}
