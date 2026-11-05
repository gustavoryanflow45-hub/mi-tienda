<?php

namespace App\Http\Resources\V1;

use App\Models\ProductStock;
use Illuminate\Http\Request;

/**
 * Ficha de producto: la tarjeta más fotos, descripción, tienda y las
 * combinaciones talla × color con su precio y su stock.
 *
 * Las combinaciones son las mismas que ofrecen la ficha web y el modal
 * rápido (Product::stockMap()): la app no puede vender una que la web ya
 * no tiene. `variation` es la clave que espera el carrito.
 *
 * @mixin \App\Models\Product
 */
class ProductDetailResource extends ProductResource
{
    public function toArray(Request $request): array
    {
        $palette = config('variants.colors', []);

        return array_merge(parent::toArray($request), [
            'photos' => collect($this->photos ?? [])->map(fn ($path) => uploaded_asset($path))->values(),
            'short_description' => $this->short_description,
            'description' => self::plainText($this->description),
            'min_qty' => (int) ($this->min_qty ?: 1),
            'shipping_cost' => round((float) $this->shipping_cost, 2),
            'shop' => $this->seller?->shop ? [
                'id' => $this->seller->shop->id,
                'name' => $this->seller->shop->name,
            ] : null,
            'variants' => [
                'size_label' => $this->sizeLabel(),
                'sizes' => $this->availableSizes(),
                'colors' => collect($this->availableColors())->map(fn ($color) => [
                    'key' => $color,
                    'label' => __($palette[$color]['label'] ?? ucfirst($color)),
                    'hex' => $palette[$color]['hex'] ?? null,
                ])->values(),
                'combinations' => collect($this->stockMap())->map(function ($entry, $variation) {
                    ['size' => $size, 'color' => $color] = ProductStock::parseVariant($variation);

                    return [
                        'variation' => (string) $variation,
                        'size' => $size,
                        'color' => $color,
                        'price' => $entry['price'],
                        'original_price' => $entry['original_price'],
                        'qty' => $entry['qty'],
                        'available' => $entry['qty'] > 0,
                    ];
                })->values(),
            ],
            'related' => ProductResource::collection($this->whenLoaded('related')),
        ]);
    }

    /**
     * La descripción la escribe el vendedor y puede traer HTML: a la app va
     * como texto (párrafos y saltos de línea respetados), nunca como HTML.
     */
    public static function plainText(?string $html): ?string
    {
        if (blank($html)) {
            return null;
        }

        // Fuera scripts y estilos con su contenido; luego, saltos de línea.
        $text = preg_replace('#<(script|style)\b[^>]*>.*?</\1\s*>#is', '', $html);
        $text = preg_replace('#<\s*(br|/p|/div|/li|/h[1-6])\s*/?>#i', "\n", $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace("/\n{3,}/", "\n\n", preg_replace('/[ \t]+/', ' ', $text)));
    }
}
