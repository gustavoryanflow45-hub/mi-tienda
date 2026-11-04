{{-- Botón aprobar / desactivar (products.approved). $compact = solo icono, para la tabla. --}}
@php $compact = $compact ?? false; @endphp
@if($product->approved)
    <form method="POST" action="{{ route('admin.products.disable', $product->id) }}" class="d-inline"
          onsubmit="return confirm(@js(__('¿Desactivar «:name»? Dejará de verse en la tienda.', ['name' => $product->name])))">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Desactivar') }}">
            <i class="las la-ban"></i>@unless($compact) {{ __('Desactivar') }}@endunless
        </button>
    </form>
@else
    <form method="POST" action="{{ route('admin.products.approve', $product->id) }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-success" title="{{ __('Aprobar') }}">
            <i class="las la-check"></i>@unless($compact) {{ __('Aprobar') }}@endunless
        </button>
    </form>
@endif
