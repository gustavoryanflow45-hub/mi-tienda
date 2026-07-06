@extends('layouts.app')

@section('title', 'Shopping Cart')

@section('extra_css')
<style>
    body { background: #f5f6f8; }
    .cart-page { padding: 30px 0 60px; }

    /* ── Tabla ── */
    .cart-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 8px rgba(0,0,0,.07); }
    .cart-table th { background: #f8f9fa; font-size: .78rem; text-transform: uppercase; letter-spacing: .5px; color: #888; font-weight: 700; padding: 12px 16px; border-bottom: 1px solid #eee; }
    .cart-table td { padding: 14px 16px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; font-size: .88rem; color: #444; }
    .cart-table tr:last-child td { border-bottom: none; }
    .cart-table tr:hover td { background: #fafafa; }

    /* ── Producto en tabla ── */
    .cart-product { display: flex; align-items: center; gap: 14px; }
    .cart-product-img { width: 64px; height: 64px; object-fit: cover; border-radius: 6px; background: #eee; flex-shrink: 0; }
    .cart-product-img-placeholder { width: 64px; height: 64px; border-radius: 6px; background: #e8eaed; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .cart-product-img-placeholder i { font-size: 1.5rem; color: #bbb; }
    .cart-product-name { font-weight: 600; color: #333; font-size: .85rem; line-height: 1.35; }
    .cart-product-variant { font-size: .75rem; color: #999; margin-top: 2px; }

    /* ── Quantity control ── */
    .qty-control { display: flex; align-items: center; gap: 6px; }
    .qty-btn { width: 28px; height: 28px; border: 1px solid #ddd; border-radius: 5px; background: #fff; cursor: pointer; font-size: 1rem; line-height: 1; display: flex; align-items: center; justify-content: center; transition: all .15s; color: #555; }
    .qty-btn:hover { border-color: #679941; color: #679941; }
    .qty-input { width: 44px; height: 28px; border: 1px solid #ddd; border-radius: 5px; text-align: center; font-size: .85rem; font-weight: 600; color: #333; }
    .qty-input:focus { outline: none; border-color: #679941; }

    /* ── Remove ── */
    .btn-remove { background: none; border: none; color: #ccc; cursor: pointer; font-size: 1.1rem; padding: 4px; transition: color .15s; }
    .btn-remove:hover { color: #e74c3c; }

    /* ── Resumen ── */
    .cart-summary { background: #fff; border-radius: 10px; box-shadow: 0 1px 8px rgba(0,0,0,.07); padding: 24px; }
    .cart-summary h3 { font-size: .95rem; font-weight: 700; color: #333; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0; }
    .summary-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; font-size: .85rem; color: #666; }
    .summary-row.total { font-weight: 700; font-size: 1rem; color: #222; margin-top: 14px; padding-top: 14px; border-top: 1px solid #eee; }
    .summary-row .val { font-weight: 600; color: #333; }
    .summary-row.total .val { color: #679941; font-size: 1.1rem; }

    .btn-checkout { display: block; width: 100%; background: #679941; color: #fff; border: none; border-radius: 8px; padding: 12px; font-size: .95rem; font-weight: 700; cursor: pointer; margin-top: 18px; letter-spacing: .3px; transition: background .2s; text-align: center; text-decoration: none; }
    .btn-checkout:hover { background: #4e7a2e; color: #fff; text-decoration: none; }

    .btn-continue { display: block; width: 100%; background: #f5f6f8; color: #555; border: 1px solid #ddd; border-radius: 8px; padding: 10px; font-size: .85rem; font-weight: 600; cursor: pointer; margin-top: 10px; text-align: center; text-decoration: none; transition: all .2s; }
    .btn-continue:hover { border-color: #679941; color: #679941; text-decoration: none; }

    /* ── Coupon ── */
    .coupon-form { display: flex; gap: 8px; margin-top: 16px; }
    .coupon-form input { flex: 1; border: 1px solid #ddd; border-radius: 6px; padding: 8px 12px; font-size: .82rem; height: 36px; }
    .coupon-form input:focus { outline: none; border-color: #679941; }
    .coupon-form button { background: #679941; color: #fff; border: none; border-radius: 6px; padding: 0 14px; font-size: .82rem; font-weight: 600; cursor: pointer; height: 36px; white-space: nowrap; }
    .coupon-form button:hover { background: #4e7a2e; }

    /* ── Empty ── */
    .cart-empty { text-align: center; background: #fff; border-radius: 10px; padding: 5rem 2rem; box-shadow: 0 1px 8px rgba(0,0,0,.07); }
    .cart-empty i { font-size: 4rem; color: #ddd; display: block; margin-bottom: 1rem; }
    .cart-empty h4 { font-size: 1.1rem; font-weight: 700; color: #aaa; margin-bottom: .5rem; }
    .cart-empty p { color: #bbb; font-size: .88rem; }

    /* ── Toast ── */
    .aiz-toast { position: fixed; bottom: 24px; right: 24px; z-index: 9999; background: #333; color: #fff; padding: 12px 20px; border-radius: 8px; font-size: .85rem; font-weight: 600; opacity: 0; transform: translateY(10px); transition: all .3s; pointer-events: none; }
    .aiz-toast.show { opacity: 1; transform: translateY(0); }
    .aiz-toast.success { background: #679941; }
    .aiz-toast.error   { background: #e74c3c; }
</style>
@endsection

@section('content')
<div class="cart-page">
    <div class="container">

        <h1 style="font-size:1.15rem; font-weight:700; color:#222; margin-bottom:20px;">
            Shopping Cart
            @if($cartItems->count() > 0)
                <span style="color:#aaa; font-size:.85rem; font-weight:400;">({{ $cartItems->sum('quantity') }} items)</span>
            @endif
        </h1>

        @if($cartItems->count() > 0)
        <div class="row">

            {{-- ── TABLA DE PRODUCTOS ── --}}
            <div class="col-lg-8 mb-4">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="cartTableBody">
                        @foreach($cartItems as $item)
                        <tr id="cart-row-{{ $item->id }}">
                            {{-- Producto --}}
                            <td>
                                <div class="cart-product">
                                    @if($item->product->thumbnail)
                                        <img src="{{ asset('storage/' . $item->product->thumbnail) }}"
                                             alt="{{ $item->product->name }}"
                                             class="cart-product-img">
                                    @else
                                        <div class="cart-product-img-placeholder">
                                            <i class="las la-image"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="cart-product-name">
                                            <a href="{{ url('/product/' . $item->product->slug) }}" class="text-reset">
                                                {{ $item->product->name }}
                                            </a>
                                        </div>
                                        @if($item->variation)
                                            <div class="cart-product-variant">{{ $item->variation }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Precio unitario --}}
                            <td>${{ number_format($item->price, 2) }}</td>

                            {{-- Cantidad --}}
                            <td>
                                <div class="qty-control">
                                    <button class="qty-btn" onclick="changeQty({{ $item->id }}, -1)">−</button>
                                    <input type="number" class="qty-input" id="qty-{{ $item->id }}"
                                           value="{{ $item->quantity }}"
                                           min="1"
                                           max="{{ $item->available_stock }}"
                                           data-max="{{ $item->available_stock }}"
                                           onchange="updateQty({{ $item->id }}, this.value)">
                                    <button class="qty-btn" onclick="changeQty({{ $item->id }}, 1)">+</button>
                                </div>
                            </td>

                            {{-- Subtotal --}}
                            <td>
                                <strong id="subtotal-{{ $item->id }}" style="color:#679941;">
                                    ${{ number_format($item->price * $item->quantity, 2) }}
                                </strong>
                            </td>

                            {{-- Eliminar --}}
                            <td>
                                <button class="btn-remove" onclick="removeItem({{ $item->id }})" title="Remove">
                                    <i class="las la-times-circle"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ── RESUMEN DEL PEDIDO ── --}}
            <div class="col-lg-4">
                <div class="cart-summary">
                    <h3>Order Summary</h3>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span class="val" id="summary-subtotal">${{ number_format($total, 2) }}</span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span class="val">Free</span>
                    </div>
                    <div class="summary-row">
                        <span>Tax</span>
                        <span class="val">$0.00</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span class="val" id="summary-total">${{ number_format($total, 2) }}</span>
                    </div>

                    <a href="{{ url('/checkout') }}" class="btn-checkout">
                        <i class="las la-lock mr-1"></i> Proceed to Checkout
                    </a>
                    <a href="{{ url('/products') }}" class="btn-continue">
                        ← Continue Shopping
                    </a>

                    {{-- Cupón --}}
                    <div class="mt-4 pt-3 border-top">
                        <p style="font-size:.8rem; color:#888; font-weight:600; margin-bottom:8px;">Have a coupon?</p>
                        <div class="coupon-form">
                            <input type="text" id="couponCode" placeholder="Enter coupon code">
                            <button type="button" onclick="applyCoupon()">Apply</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        @else
        {{-- CARRITO VACÍO --}}
        <div class="cart-empty">
            <i class="las la-shopping-cart"></i>
            <h4>Your cart is empty</h4>
            <p>Looks like you haven't added anything to your cart yet.</p>
            <a href="{{ url('/products') }}" class="btn-checkout mt-4 d-inline-block" style="width:auto; padding: 12px 32px;">
                Start Shopping
            </a>
        </div>
        @endif

    </div>
</div>

{{-- Toast notification --}}
<div class="aiz-toast" id="aizToast"></div>
@endsection

@section('extra_js')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

function showToast(msg, type = 'success') {
    const t = document.getElementById('aizToast');
    t.textContent = msg;
    t.className = 'aiz-toast ' + type + ' show';
    setTimeout(() => t.classList.remove('show'), 3000);
}

function changeQty(cartId, delta) {
    const input  = document.getElementById('qty-' + cartId);
    const max    = parseInt(input.dataset.max) || 999;
    let newVal   = parseInt(input.value) + delta;

    if (newVal < 1)   newVal = 1;
    if (newVal > max) {
        showToast('Only ' + max + ' units available in stock', 'error');
        newVal = max;
    }

    input.value = newVal;
    updateQty(cartId, newVal);
}

function updateQty(cartId, qty) {
    const input = document.getElementById('qty-' + cartId);
    const max   = parseInt(input.dataset.max) || 999;
    qty         = Math.min(Math.max(1, parseInt(qty)), max);
    input.value = qty;

    fetch('{{ url("/cart/update-quantity") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ cart_id: cartId, quantity: qty })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            document.getElementById('subtotal-' + cartId).textContent = '$' + data.subtotal;
            document.getElementById('summary-subtotal').textContent = '$' + data.cart_total;
            document.getElementById('summary-total').textContent = '$' + data.cart_total;
            updateCartCount(data.cart_count);
        }
    });
}

function removeItem(cartId) {
    if (!confirm('Remove this item from cart?')) return;

    fetch('{{ route("cart.remove") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ cart_id: cartId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            const row = document.getElementById('cart-row-' + cartId);
            row.style.opacity = '0';
            row.style.transition = 'opacity 0.3s';
            setTimeout(() => {
                row.remove();
                document.getElementById('summary-subtotal').textContent = '$' + data.cart_total;
                document.getElementById('summary-total').textContent = '$' + data.cart_total;
                updateCartCount(data.cart_count);
                if (data.cart_count === 0) location.reload();
            }, 300);
            showToast('Item removed from cart');
        }
    });
}

function applyCoupon() {
    const code = document.getElementById('couponCode').value.trim();
    if (!code) { showToast('Enter a coupon code', 'error'); return; }
    showToast('Coupon feature coming soon', 'error');
}

function updateCartCount(count) {
    if (count !== undefined) {
        document.querySelectorAll('.cart-count').forEach(el => el.textContent = count);
    }
}
</script>
@endsection
