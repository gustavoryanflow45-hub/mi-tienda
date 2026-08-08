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

    /** Etiqueta legible para carrito, pedidos y correos: "Talla 40 · Negro". */
    public function getVariantLabelAttribute(): string
    {
        $colors = config('variants.colors', []);

        return collect([
            filled($this->size) ? 'Talla ' . $this->size : null,
            filled($this->color) ? ($colors[$this->color]['label'] ?? ucfirst($this->color)) : null,
        ])->filter()->implode(' · ');
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
