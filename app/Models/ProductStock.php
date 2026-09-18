<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStock extends Model
{
    protected $fillable = ['product_id', 'size', 'color', 'variant', 'price', 'qty', 'sku'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Clave de la combinación, con la que el carrito y el detalle se
     * entienden: "40-negro", "M", "negro", o cadena vacía si el producto
     * no tiene variantes.
     *
     * El orden (talla primero) tiene que coincidir con el que arma el
     * JavaScript del detalle de producto.
     */
    public static function buildVariant(?string $size, ?string $color): string
    {
        return collect([$size, $color])
            ->filter(fn ($part) => filled($part))
            ->implode('-');
    }

    /**
     * Inversa de buildVariant(): separa la clave en talla y color.
     *
     * Una clave de una sola parte es ambigua — "M" es talla y "negro" es
     * color — así que se decide mirando la paleta: lo que está en
     * config('variants.colors') es color, y cualquier otra cosa, talla.
     *
     * Ninguna talla ni ningún color de la configuración lleva "-", de modo
     * que dos partes son siempre talla y color en ese orden.
     */
    public static function parseVariant(?string $variant): array
    {
        if (blank($variant)) {
            return ['size' => null, 'color' => null];
        }

        $parts = explode('-', $variant, 2);

        if (count($parts) === 2) {
            return ['size' => $parts[0], 'color' => $parts[1]];
        }

        return array_key_exists($parts[0], config('variants.colors', []))
            ? ['size' => null, 'color' => $parts[0]]
            : ['size' => $parts[0], 'color' => null];
    }

    /**
     * Descompone una clave de variante en lo que hace falta para pintarla:
     * talla con su etiqueta ("Talla US 9"), color con su nombre y su hex, y
     * un texto plano para correos y sitios sin maquetación.
     *
     * Es el único sitio que traduce la clave interna a algo legible; carrito,
     * pedidos, panel del vendedor y almacén pasan todos por aquí, para que la
     * misma variante no se lea de dos formas distintas según la pantalla.
     *
     * El producto es opcional pero da la etiqueta correcta de la talla
     * ("Talla US" para calzado, "Cintura" para pantalones); sin él se cae a
     * "Talla", que es lo que aplica a la mayoría de los tipos.
     */
    public static function describeVariant(?string $variant, ?Product $product = null): array
    {
        ['size' => $size, 'color' => $color] = static::parseVariant($variant);

        $colors    = config('variants.colors', []);
        $sizeLabel = $product?->sizeLabel() ?? 'Talla';

        $colorLabel = filled($color)
            ? ($colors[$color]['label'] ?? ucfirst($color))
            : null;

        return [
            'size'        => filled($size) ? $size : null,
            'size_label'  => $sizeLabel,
            'color'       => filled($color) ? $color : null,
            'color_label' => $colorLabel,
            'color_hex'   => filled($color) ? ($colors[$color]['hex'] ?? '#888') : null,
            'text'        => collect([
                filled($size) ? $sizeLabel . ' ' . $size : null,
                $colorLabel,
            ])->filter()->implode(' · '),
        ];
    }

    /** Etiqueta legible para carrito, pedidos y correos: "Talla 40 · Negro". */
    public function getVariantLabelAttribute(): string
    {
        return $this->variant_parts['text'];
    }

    /** Las mismas partes que describeVariant(), para pintar el chip de color. */
    public function getVariantPartsAttribute(): array
    {
        return static::describeVariant(
            static::buildVariant($this->size, $this->color),
            $this->relationLoaded('product') ? $this->product : null,
        );
    }

    /** Hex del color para pintar el chip; null si la fila no tiene color. */
    public function getColorHexAttribute(): ?string
    {
        if (blank($this->color)) {
            return null;
        }

        return config("variants.colors.{$this->color}.hex", '#888');
    }

    /**
     * Mantiene `variant` sincronizado con size/color. Así una fila guardada
     * desde cualquier sitio (formulario, seeder, tinker) queda consistente
     * sin que cada llamador se acuerde de componer la cadena.
     */
    protected static function booted(): void
    {
        static::saving(function (self $stock) {
            $stock->variant = static::buildVariant($stock->size, $stock->color) ?: null;
        });
    }
}
