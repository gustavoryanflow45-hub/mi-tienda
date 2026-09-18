{{--
    Chip de variante (talla + color) para carrito, checkout, pedidos, panel
    del vendedor y almacén.

    Espera $parts tal como los devuelve ProductStock::describeVariant(), es
    decir el accessor variant_parts de Cart / OrderDetail / ProductStock:

        @include('partials.variant-badge', ['parts' => $item->variant_parts])

    Opcionales: $small para las tablas densas y $fallback con el texto que
    se pinta cuando la línea no tiene variación ("—" en las tablas, nada en
    las fichas, donde el hueco se nota).

    Los estilos van en línea a propósito: cada página del proyecto lleva su
    propio bloque <style> y este parcial se incrusta en todas ellas.
--}}
@php
    $parts    = $parts ?? [];
    $small    = $small ?? false;
    $fallback = $fallback ?? null;
@endphp

@if(! empty($parts['size']) || ! empty($parts['color']))
    <span style="display:inline-flex; flex-wrap:wrap; align-items:center; gap:6px; font-size:{{ $small ? '.7rem' : '.75rem' }}; line-height:1.4;">
        @if(! empty($parts['size']))
            <span style="display:inline-flex; align-items:center; gap:4px; padding:1px 8px; border:1px solid #e0e0e0; border-radius:20px; background:#fafafa; white-space:nowrap;">
                <span style="color:#999;">{{ $parts['size_label'] }}</span>
                <strong style="color:#333;">{{ $parts['size'] }}</strong>
            </span>
        @endif
        @if(! empty($parts['color']))
            <span style="display:inline-flex; align-items:center; gap:4px; color:#666; white-space:nowrap;">
                <span style="width:11px; height:11px; border-radius:50%; border:1px solid rgba(0,0,0,.18); background:{{ $parts['color_hex'] }};"></span>
                {{ $parts['color_label'] }}
            </span>
        @endif
    </span>
@elseif(filled($fallback))
    <span style="color:#bbb;">{{ $fallback }}</span>
@endif
