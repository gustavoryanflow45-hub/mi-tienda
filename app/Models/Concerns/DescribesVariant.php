<?php

namespace App\Models\Concerns;

use App\Models\ProductStock;

/**
 * Traduce la columna `variation` — la clave que guardan carrito y pedido,
 * "40-negro" — a algo que una persona pueda leer.
 *
 * Carrito y línea de pedido guardan la misma clave y hasta ahora cada vista
 * la pintaba tal cual: el comprador veía "40-negro" en el carrito y el
 * almacén lo mismo al ir a separar la caja. La descomposición vive en
 * ProductStock (que es quien compone la clave); esto solo la acerca a los
 * dos modelos que la almacenan.
 */
trait DescribesVariant
{
    /** Talla, color, hex y texto de la variación de esta línea. */
    public function getVariantPartsAttribute(): array
    {
        return ProductStock::describeVariant($this->variation, $this->product);
    }

    /** "Talla US 9 · Negro", o cadena vacía si la línea no tiene variación. */
    public function getVariantLabelAttribute(): string
    {
        return $this->variant_parts['text'];
    }

    /** Si hay algo que mostrar; evita pintar el hueco de la etiqueta vacía. */
    public function hasVariant(): bool
    {
        return $this->variant_label !== '';
    }
}
