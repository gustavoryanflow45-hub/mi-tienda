@extends('layouts.app')

@section('title', 'Mi Lista de Deseos')

@section('content')
<div style="background:#f2f3f8; padding:30px 0 60px; min-height:60vh;">
    <div class="container">

        <h1 style="font-size:1.2rem; font-weight:700; color:#222; margin-bottom:24px;">
            <i class="las la-heart" style="color:#679941;"></i> Mi Lista de Deseos
        </h1>

        @if($wishlist->isEmpty())
            <div style="background:#fff; border-radius:10px; box-shadow:0 1px 8px rgba(0,0,0,.07); padding:60px 20px; text-align:center; color:#bbb;">
                <i class="las la-heart-broken" style="font-size:3rem; display:block; margin-bottom:12px;"></i>
                <p style="font-size:.95rem; font-weight:600; color:#888;">Tu lista de deseos está vacía.</p>
                <a href="{{ route('products.index') }}" style="color:#679941; font-size:.85rem;">← Explorar productos</a>
            </div>
        @else
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:16px;">
                @foreach($wishlist as $item)
                    @if($item->product)
                        <div style="background:#fff; border-radius:10px; box-shadow:0 1px 8px rgba(0,0,0,.07); overflow:hidden; position:relative;">
                            <button onclick="removeWishlist({{ $item->product_id }}, this)"
                                style="position:absolute; top:8px; right:8px; background:rgba(255,255,255,.9); border:none; border-radius:50%; width:30px; height:30px; cursor:pointer; font-size:14px; color:#e74c3c; display:flex; align-items:center; justify-content:center; z-index:1;">
                                <i class="las la-times"></i>
                            </button>
                            <a href="{{ route('products.show', $item->product->slug) }}">
                                @if($item->product->thumbnail)
                                    <img src="{{ asset('storage/' . $item->product->thumbnail) }}"
                                         alt="{{ $item->product->name }}"
                                         style="width:100%; height:180px; object-fit:cover;">
                                @else
                                    <div style="width:100%; height:180px; background:#f0f0f0; display:flex; align-items:center; justify-content:center;">
                                        <i class="las la-image" style="font-size:2rem; color:#ccc;"></i>
                                    </div>
                                @endif
                            </a>
                            <div style="padding:12px;">
                                <a href="{{ route('products.show', $item->product->slug) }}"
                                   style="font-size:.85rem; font-weight:600; color:#333; text-decoration:none; display:block; margin-bottom:6px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    {{ $item->product->name }}
                                </a>
                                <div style="font-size:.9rem; font-weight:700; color:#679941; margin-bottom:10px;">
                                    ${{ number_format($item->product->discounted_price, 2) }}
                                </div>
                                <button onclick="addToCart({{ $item->product->id }})"
                                    style="width:100%; background:#679941; color:#fff; border:none; border-radius:6px; padding:8px; font-size:.82rem; font-weight:600; cursor:pointer;">
                                    <i class="las la-shopping-cart"></i> Añadir al carrito
                                </button>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif

    </div>
</div>
@endsection

@section('extra_js')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

function removeWishlist(productId, btn) {
    fetch('{{ route('wishlist.remove') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ product_id: productId }),
    }).then(() => btn.closest('div[style]').remove());
}

function addToCart(productId) {
    fetch('{{ route('cart.add') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ product_id: productId, quantity: 1 }),
    }).then(r => r.json()).then(data => {
        if (data.status === 'success') alert('Añadido al carrito.');
    });
}
</script>
@endsection
