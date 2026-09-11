@if($cartItems->count() > 0)
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <span class="fw-700 fs-14">Cart ({{ $cartItems->sum('quantity') }} items)</span>
        <a href="{{ route('cart.index') }}" class="text-primary fs-13">View All</a>
    </div>
    <div style="max-height: 300px; overflow-y: auto;">
        @foreach($cartItems as $item)
        <div class="d-flex align-items-center p-3 border-bottom" id="mini-cart-item-{{ $item->id }}">
            {{-- Imagen --}}
            <a href="{{ url('/product/' . $item->product->slug) }}" class="mr-3 flex-shrink-0">
                @if($item->product->thumbnail)
                    <img src="{{ asset('storage/' . $item->product->thumbnail) }}"
                         class="rounded" width="50" height="50" style="object-fit:cover;">
                @else
                    <div class="rounded bg-light d-flex align-items-center justify-content-center"
                         style="width:50px;height:50px;">
                        <i class="las la-image text-muted"></i>
                    </div>
                @endif
            </a>
            {{-- Info --}}
            <div class="flex-grow-1 min-w-0">
                <a href="{{ url('/product/' . $item->product->slug) }}"
                   class="d-block text-reset fw-600 fs-13 text-truncate">
                    {{ $item->product->name }}
                </a>
                @if($item->variation)
                    <span class="fs-11 text-muted">{{ $item->variation }}</span>
                @endif
                <div class="fs-13 mt-1">
                    <span class="text-muted">{{ $item->quantity }} ×</span>
                    <span class="fw-700 text-primary ml-1">${{ number_format($item->price, 2) }}</span>
                </div>
            </div>
            {{-- Eliminar --}}
            <button class="btn p-1 ml-2 text-muted"
                    onclick="miniCartRemove({{ $item->id }})"
                    title="Remove">
                <i class="las la-times"></i>
            </button>
        </div>
        @endforeach
    </div>
    {{-- Total + botón --}}
    <div class="p-3">
        <div class="d-flex justify-content-between fs-13 text-muted mb-1">
            <span>Subtotal</span>
            <span>${{ number_format($total, 2) }}</span>
        </div>
        <div class="d-flex justify-content-between fs-13 text-muted mb-2">
            <span>Envío</span>
            <span>{{ $shippingTotal > 0 ? '$'.number_format($shippingTotal, 2) : 'Gratis' }}</span>
        </div>
        <div class="d-flex justify-content-between mb-3">
            <span class="fw-700">Total</span>
            <span class="fw-700 text-primary">${{ number_format($grandTotal, 2) }}</span>
        </div>
        <a href="{{ route('cart.index') }}"
           class="btn btn-primary btn-block fw-700">
            <i class="las la-shopping-cart mr-1"></i> View Cart
        </a>
    </div>
@else
    <div class="text-center p-4">
        <i class="las la-frown la-3x opacity-60 mb-3 d-block"></i>
        <h3 class="h6 fw-700">Your Cart is empty</h3>
        <a href="{{ route('products.index') }}" class="btn btn-sm btn-primary mt-2">Shop Now</a>
    </div>
@endif