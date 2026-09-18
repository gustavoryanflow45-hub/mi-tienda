{{--
    Ficha compacta del modal "añadir al carrito" que abre la tarjeta de
    producto, para elegir talla y color desde la rejilla sin entrar a la
    ficha completa.

    La sirve CartController@modal ya renderizada: el JS de layouts/app.blade.php
    la inyecta tal cual. El comportamiento vive allí, delegado sobre #qa-root,
    para no reinyectar scripts en cada apertura del modal.

    Los datos que necesita ese JS viajan en los data-* de #qa-root; el mapa de
    combinaciones sale de Product::stockMap(), el mismo que usa la ficha.
--}}
@php
    $finalPrice = $product->unit_price;
    if ($product->discount > 0) {
        $finalPrice = $product->discount_type === 'percent'
            ? $product->unit_price * (1 - $product->discount / 100)
            : $product->unit_price - $product->discount;
    }

    $sizes      = $product->availableSizes();
    $colors     = $product->availableColors();
    $palette    = config('variants.colors', []);
    $totalStock = $product->stocks->sum('qty');
@endphp

<style>
    .qa-wrap { display:flex; gap:20px; padding:24px; flex-wrap:wrap; }
    .qa-img { width:180px; height:180px; object-fit:cover; border-radius:8px; background:#f6f6f6; flex-shrink:0; }
    .qa-main { flex:1; min-width:230px; }
    .qa-name { font-size:1.05rem; font-weight:700; color:#222; margin:0 0 6px; line-height:1.3; }
    .qa-price { font-size:1.2rem; font-weight:700; color:#679941; margin-bottom:14px; }
    .qa-price del { font-size:.85rem; color:#aaa; font-weight:400; margin-right:6px; }
    .qa-field { margin-bottom:12px; }
    .qa-label { display:block; font-size:.78rem; color:#888; margin-bottom:6px; }
    .qa-chips { display:flex; flex-wrap:wrap; gap:6px; }
    .qa-size { min-width:38px; padding:5px 10px; border:1px solid #ddd; border-radius:6px; font-size:.82rem;
               text-align:center; color:#444; background:#fff; transition:all .15s; }
    .qa-size.selected { border-color:#679941; background:#679941; color:#fff; }
    .qa-color { width:24px; height:24px; border-radius:50%; display:block; border:2px solid transparent;
                box-shadow:0 0 0 1px rgba(0,0,0,.15); transition:all .15s; }
    .qa-color.selected { border-color:#fff; box-shadow:0 0 0 2px #679941; }
    .qa-size.no-stock, .qa-color.no-stock { opacity:.3; text-decoration:line-through; cursor:not-allowed; }
    .qa-color.no-stock { text-decoration:none; }
    .qa-qty { display:flex; align-items:center; gap:10px; }
    .qa-qty-control { display:flex; align-items:center; gap:6px; }
    .qa-qty-btn { width:28px; height:28px; border:1px solid #ddd; border-radius:5px; background:#fff;
                  cursor:pointer; font-size:1rem; line-height:1; color:#555; }
    .qa-qty-input { width:52px; height:28px; border:1px solid #ddd; border-radius:5px; text-align:center;
                    font-size:.85rem; font-weight:600; color:#333; }
    .qa-stock { font-size:.78rem; color:#888; }
    .qa-cta { display:flex; gap:8px; margin-top:16px; flex-wrap:wrap; }
    .qa-add { flex:1; min-width:150px; padding:10px 16px; border:0; border-radius:6px; background:#679941;
              color:#fff; font-weight:600; font-size:.88rem; cursor:pointer; }
    .qa-add:disabled { opacity:.55; cursor:not-allowed; }
    .qa-detail { padding:10px 16px; border:1px solid #ddd; border-radius:6px; color:#555; font-size:.88rem;
                 text-decoration:none; display:inline-flex; align-items:center; }
    .qa-detail:hover { color:#679941; border-color:#679941; text-decoration:none; }
    .qa-out { padding:10px 16px; border-radius:6px; background:#f4f4f4; color:#999; font-size:.88rem; }
</style>

<div id="qa-root" class="qa-wrap"
     data-product-id="{{ $product->id }}"
     data-has-sizes="{{ count($sizes) > 0 ? '1' : '0' }}"
     data-has-colors="{{ count($colors) > 0 ? '1' : '0' }}"
     data-total-stock="{{ $totalStock }}"
     data-stock-map="{{ json_encode($product->stockMap()) }}">

    <img src="{{ uploaded_asset($product->thumbnail) }}" alt="{{ $product->name }}" class="qa-img"
         onerror="this.onerror=null;this.src='{{ asset('assets/img/placeholder.jpg') }}';">

    <div class="qa-main">
        <h5 class="qa-name">{{ $product->name }}</h5>

        <div class="qa-price">
            @if($product->discount > 0)
                <del>${{ number_format($product->unit_price, 2) }}</del>
            @endif
            <span id="qa-price-value">${{ number_format($finalPrice, 2) }}</span>
        </div>

        @if(count($sizes) > 0)
            <div class="qa-field">
                <span class="qa-label">{{ $product->sizeLabel() }}</span>
                <div class="qa-chips">
                    @foreach($sizes as $size)
                        <label style="cursor:pointer; margin:0;" title="{{ $size }}">
                            <input type="radio" name="qa-size" value="{{ $size }}" style="display:none;">
                            <span class="qa-size" data-size="{{ $size }}">{{ $size }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        @if(count($colors) > 0)
            <div class="qa-field">
                <span class="qa-label">{{ __('Color') }}</span>
                <div class="qa-chips">
                    @foreach($colors as $color)
                        @php
                            $hex   = $palette[$color]['hex']   ?? '#888';
                            $label = $palette[$color]['label'] ?? ucfirst($color);
                        @endphp
                        <label style="cursor:pointer; margin:0;" title="{{ $label }}">
                            <input type="radio" name="qa-color" value="{{ $color }}" style="display:none;">
                            <span class="qa-color" data-color="{{ $color }}"
                                  style="background:{{ $hex }};{{ strtolower($hex) === '#ffffff' ? 'box-shadow:0 0 0 1px #ccc;' : '' }}"></span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="qa-field qa-qty">
            <span class="qa-label" style="margin:0;">{{ __('Cantidad') }}</span>
            <div class="qa-qty-control">
                <button type="button" class="qa-qty-btn" data-step="-1">&minus;</button>
                <input type="number" id="qa-qty" class="qa-qty-input" value="1" min="1" max="{{ max(1, $totalStock) }}">
                <button type="button" class="qa-qty-btn" data-step="1">+</button>
            </div>
            <span class="qa-stock" id="qa-stock">({{ __(':qty disponibles', ['qty' => $totalStock]) }})</span>
        </div>

        <div class="qa-cta">
            @if($totalStock > 0)
                <button type="button" class="qa-add">
                    <i class="las la-shopping-cart"></i> {{ __('Añadir al carrito') }}
                </button>
            @else
                <span class="qa-out">{{ __('Agotado') }}</span>
            @endif
            <a href="{{ route('products.show', $product->slug) }}" class="qa-detail">{{ __('Ver ficha completa') }}</a>
        </div>
    </div>
</div>
