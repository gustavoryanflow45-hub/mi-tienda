{{-- Estado de venta de un producto: lo decide su vendedor (published) y el panel (approved). --}}
@if(! $product->approved)
    <span class="pill pill-off">{{ __('Desactivado por moderación') }}</span>
@elseif(! $product->published)
    <span class="pill pill-muted">{{ __('Sin publicar por su vendedor') }}</span>
@else
    <span class="pill pill-ok">{{ __('A la venta') }}</span>
@endif
