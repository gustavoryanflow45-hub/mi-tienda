{{-- resources/views/partials/product-card.blade.php --}}
<div class="aiz-card-box border border-light rounded hov-shadow-md mt-1 mb-2 has-transition bg-white">
    <div class="position-relative">
        <a href="{{ route('products.show', $product->slug) }}" class="d-block">
            <img
                class="img-fit lazyload mx-auto h-140px h-md-210px"
                src="{{ asset('assets/img/placeholder.jpg') }}"
                data-src="{{ uploaded_asset($product->thumbnail) }}"
                alt="{{ $product->name }}"
                onerror="this.onerror=null;this.src='{{ asset('assets/img/placeholder.jpg') }}';"
            >
        </a>
        <div class="absolute-top-right aiz-p-hov-icon">
            <a href="javascript:void(0)" onclick="addToWishList({{ $product->id }})"
               data-toggle="tooltip" data-title="Add to wishlist" data-placement="left">
                <i class="la la-heart-o"></i>
            </a>
            <a href="javascript:void(0)" onclick="addToCompare({{ $product->id }})"
               data-toggle="tooltip" data-title="Add to compare" data-placement="left">
                <i class="las la-sync"></i>
            </a>
            <a href="javascript:void(0)" onclick="showAddToCartModal({{ $product->id }})"
               data-toggle="tooltip" data-title="Add to cart" data-placement="left">
                <i class="las la-shopping-cart"></i>
            </a>
        </div>
    </div>
    <div class="p-md-3 p-2 text-left">
        <div class="fs-15">
            @if($product->discount > 0)
                <del class="fw-400 opacity-50 mr-1">${{ number_format($product->unit_price, 2) }}</del>
                <span class="fw-700 text-primary">${{ number_format($product->discounted_price, 2) }}</span>
            @else
                <span class="fw-700 text-primary">${{ number_format($product->unit_price, 2) }}</span>
            @endif
        </div>
        <div class="rating rating-sm mt-1">
            @for($i = 1; $i <= 5; $i++)
                <i class="las la-star{{ $i <= round($product->rating ?? 0) ? ' text-warning' : '' }}"></i>
            @endfor
        </div>
        <h3 class="fw-600 fs-13 text-truncate-2 lh-1-4 mb-0 h-35px">
            <a href="{{ route('products.show', $product->slug) }}" class="d-block text-reset">
                {{ $product->name }}
            </a>
        </h3>
    </div>
</div>
