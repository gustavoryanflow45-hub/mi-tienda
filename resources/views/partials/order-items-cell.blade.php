{{--
    Artículos de un pedido con su variante, para las tablas del panel del
    vendedor y del almacén.

    Ninguna de las dos tablas mostraba las líneas del pedido, solo el total:
    quien prepara la caja tiene que saber qué talla y qué color separar, no
    cuántos bultos van, y el vendedor necesita lo mismo para despacharlo.

    Espera $details, una colección de OrderDetail.
--}}
<div style="display:flex; flex-direction:column; gap:8px; min-width:180px;">
    @forelse($details as $detail)
        <div style="line-height:1.4;">
            <div style="font-weight:600; color:#333; font-size:.8rem;">
                {{ $detail->product?->name ?? $detail->product_name }}
                <span style="color:#888; font-weight:400;">&times; {{ $detail->quantity }}</span>
            </div>
            @include('partials.variant-badge', [
                'parts'    => $detail->variant_parts,
                'small'    => true,
                'fallback' => 'Sin talla ni color',
            ])
        </div>
    @empty
        <span style="color:#bbb;">—</span>
    @endforelse
</div>
