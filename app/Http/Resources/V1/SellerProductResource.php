<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Producto tal como lo edita su vendedor: los campos del formulario y una
 * fila por combinación de stock. `published` es del vendedor y `approved`
 * del panel; solo con los dos se vende (`on_sale`). Espera category y
 * stocks cargados.
 *
 * @mixin \App\Models\Product
 */
class SellerProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'category' => $this->category ? ['id' => $this->category->id, 'name' => $this->category->name] : null,
            'brand_id' => $this->brand_id,
            'unit_price' => round((float) $this->unit_price, 2),
            'purchase_price' => $this->purchase_price !== null ? round((float) $this->purchase_price, 2) : null,
            'discount' => round((float) $this->discount, 2),
            'discount_type' => $this->discount_type === 'amount' ? 'amount' : 'percent',
            'final_price' => $this->finalPrice(),
            'unit' => $this->unit,
            // El declarado por el producto; null = el de su categoría.
            'variant_type' => $this->variant_type,
            'size_label' => $this->usesSizes() ? $this->sizeLabel() : null,
            'short_description' => $this->short_description,
            // HTML tal como lo guardó el vendedor (es su propio texto).
            'description' => $this->description,
            'min_qty' => (int) $this->min_qty,
            'low_stock_qty' => (int) $this->low_stock_qty,
            'shipping_cost' => round((float) $this->shipping_cost, 2),
            'published' => (bool) $this->published,
            'approved' => (bool) $this->approved,
            'on_sale' => (bool) $this->published && (bool) $this->approved,
            'thumbnail_url' => uploaded_asset($this->thumbnail),
            'photos' => collect($this->photos ?? [])->map(fn ($path) => uploaded_asset($path))->values(),
            'stocks' => $this->stocks->map(fn ($stock) => [
                'id' => $stock->id,
                'variation' => (string) $stock->variant,
                'size' => $stock->size,
                'color' => $stock->color,
                'price' => round((float) $stock->price, 2),
                'qty' => (int) $stock->qty,
                'sku' => $stock->sku,
            ])->values(),
            'total_qty' => (int) $this->stocks->sum('qty'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
