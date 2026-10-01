{{--
    Sugerencias del buscador de la cabecera. La sirve SearchController@ajax
    ya renderizada y el JS de layouts/app.blade.php la inyecta en
    #search-content tal cual (con .html()); por eso no es JSON.
--}}
<div class="px-3 py-2 fs-11 fw-600 text-uppercase opacity-60 border-bottom">{{ __('Productos') }}</div>

<ul class="list-unstyled mb-0">
    @foreach($products as $product)
        <li class="border-bottom">
            <a href="{{ route('products.show', $product->slug) }}" class="d-flex align-items-center px-3 py-2 text-reset hov-bg-soft-primary">
                <img loading="lazy" src="{{ uploaded_asset($product->thumbnail) }}" alt="{{ $product->name }}"
                     class="rounded mr-3 flex-shrink-0" style="width:40px; height:40px; object-fit:cover; background:#f5f5f5;"
                     onerror="this.onerror=null;this.src='{{ asset('assets/img/placeholder.jpg') }}';">
                <span class="flex-grow-1" style="min-width:0;">
                    <span class="d-block text-truncate fs-14 fw-600">{{ $product->name }}</span>
                    <span class="d-block fs-13 fw-700 text-primary">${{ number_format($product->discounted_price, 2) }}</span>
                </span>
            </a>
        </li>
    @endforeach
</ul>

<a href="{{ route('search', ['keyword' => $keyword]) }}" class="d-block px-3 py-2 text-center fs-13 fw-600 text-primary">
    {{ __('Ver todos los resultados') }}
</a>
