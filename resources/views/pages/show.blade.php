@extends('layouts.app')

@section('title', $product->name)
@section('meta_description', $product->meta_description ?? $product->short_description)

@section('extra_css')
<style>
    body { background: #f5f6f8; }
    .product-page { padding: 20px 0 50px; }

    /* ── Breadcrumb ── */
    .breadcrumb-bar { font-size:.82rem; color:#888; margin-bottom:16px; }
    .breadcrumb-bar a { color:#679941; text-decoration:none; }
    .breadcrumb-bar a:hover { text-decoration:underline; }
    .breadcrumb-bar span { margin:0 5px; color:#ccc; }

    /* ── Main card ── */
    .product-main {
        background:#fff;
        box-shadow:0 1px 4px rgba(0,0,0,.08);
        padding:24px 28px;
        margin-bottom:16px;
        border-radius:4px;
    }

    /* ── Galería: thumbs verticales + imagen principal ── */
    .gallery-col { display:flex; gap:10px; }
    .gallery-thumbs-vert { display:flex; flex-direction:column; gap:8px; flex-shrink:0; }
    .gallery-thumb {
        width:56px; height:56px;
        border:2px solid #e0e0e0; border-radius:4px;
        overflow:hidden; cursor:pointer; background:#f5f5f5;
        transition:border-color .2s; flex-shrink:0;
    }
    .gallery-thumb.active { border-color:#679941; }
    .gallery-thumb img { width:100%; height:100%; object-fit:cover; display:block; }

    .gallery-main-wrap {
        flex:1; border:1px solid #e8e8e8; border-radius:4px;
        overflow:hidden; background:#fff;
        display:flex; align-items:center; justify-content:center;
        min-height:340px;
    }
    .gallery-main-wrap img {
        max-width:100%; max-height:380px;
        object-fit:contain; transition:transform .3s;
    }
    .gallery-main-wrap img:hover { transform:scale(1.04); }

    /* ── Info panel ── */
    .info-panel { padding-left:24px; }
    @media(max-width:768px){ .info-panel{ padding-left:0; margin-top:20px; } }

    .product-title { font-size:1rem; font-weight:600; color:#222; line-height:1.5; margin-bottom:10px; }

    /* Estrellas */
    .stars-row {
        display:flex; align-items:center; gap:6px;
        margin-bottom:0; padding-bottom:12px;
        border-bottom:1px solid #f0f0f0;
    }
    .stars { color:#ccc; font-size:.85rem; }
    .stars .filled { color:#f0ad00; }
    .reviews-count { font-size:.8rem; color:#888; }

    /* ── Filas de info ── */
    .info-row {
        display:flex; align-items:flex-start;
        padding:11px 0; border-bottom:1px solid #f5f5f5;
    }
    .info-label { min-width:90px; font-size:.83rem; color:#999; flex-shrink:0; padding-top:2px; }
    .info-value  { font-size:.87rem; color:#333; flex:1; }

    /* Precio */
    .price-big { font-size:1.55rem; font-weight:700; color:#679941; }
    .price-unit { font-size:.82rem; color:#888; font-weight:400; margin-left:3px; }
    .price-original { font-size:.85rem; color:#bbb; text-decoration:line-through; margin-left:10px; }
    .discount-pill {
        background:#fee; color:#e74c3c; border-radius:20px;
        padding:2px 8px; font-size:.72rem; font-weight:700; margin-left:8px;
    }

    /* Seller */
    .seller-row { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    .seller-name { font-size:.87rem; color:#333; font-weight:600; }
    .btn-msg-seller {
        background:#fff; border:1px solid #679941; color:#679941;
        border-radius:4px; padding:4px 12px; font-size:.78rem; font-weight:600;
        cursor:pointer; text-decoration:none;
        display:inline-flex; align-items:center; gap:4px; transition:background .2s;
    }
    .btn-msg-seller:hover { background:#f0f8ea; text-decoration:none; color:#679941; }

    /* ── Tallas ── */
    .size-chips { display:flex; flex-wrap:wrap; gap:7px; }
    .size-chip {
        min-width:38px; height:34px; padding:0 10px;
        border:1px solid #ddd; border-radius:3px;
        background:#fff; color:#333; font-size:.83rem; font-weight:600;
        cursor:pointer; display:flex; align-items:center; justify-content:center;
        transition:all .15s; user-select:none;
    }
    .size-chip:hover  { border-color:#679941; color:#679941; }
    .size-chip.selected { border-color:#679941; background:#679941; color:#fff; }
    .size-chip.no-stock { opacity:.4; cursor:not-allowed; border-style:dashed; }

    /* ── Colores ── */
    .color-chips { display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
    .color-chip {
        width:28px; height:28px; border-radius:3px;
        border:2px solid transparent; cursor:pointer;
        transition:border-color .15s, transform .15s; position:relative;
    }
    .color-chip:hover { transform:scale(1.12); }
    .color-chip.selected { border-color:#333; }
    .color-chip.selected::after {
        content:''; position:absolute; inset:-4px;
        border:2px solid #679941; border-radius:5px;
    }

    /* ── Cantidad ── */
    .qty-row { display:flex; align-items:center; gap:12px; }
    .qty-control {
        display:flex; align-items:center;
        border:1px solid #ddd; border-radius:3px; overflow:hidden;
    }
    .qty-btn {
        width:32px; height:32px; border:none; background:#f5f5f5;
        font-size:1.1rem; font-weight:700; color:#555; cursor:pointer;
        display:flex; align-items:center; justify-content:center; transition:background .15s;
    }
    .qty-btn:hover { background:#e0e0e0; }
    .qty-input {
        width:44px; height:32px; border:none;
        border-left:1px solid #ddd; border-right:1px solid #ddd;
        text-align:center; font-size:.9rem; font-weight:600; color:#333; outline:none;
    }
    .stock-available { font-size:.8rem; color:#888; }

    /* ── CTA botones ── */
    .cta-row { display:flex; gap:10px; flex-wrap:wrap; margin:14px 0 0; }
    .btn-addcart {
        display:inline-flex; align-items:center; gap:6px;
        padding:10px 22px; border-radius:4px;
        border:2px solid #679941; background:#fff;
        color:#679941; font-size:.88rem; font-weight:700;
        cursor:pointer; transition:all .2s; text-decoration:none;
    }
    .btn-addcart:hover { background:#f0f8ea; }
    .btn-buynow {
        display:inline-flex; align-items:center; gap:6px;
        padding:10px 22px; border-radius:4px;
        border:2px solid #679941; background:#679941;
        color:#fff; font-size:.88rem; font-weight:700;
        cursor:pointer; transition:opacity .2s; text-decoration:none;
    }
    .btn-buynow:hover { opacity:.88; color:#fff; }
    .btn-outstock {
        padding:10px 22px; border-radius:4px;
        border:2px solid #ccc; background:#f5f5f5;
        color:#aaa; font-size:.88rem; font-weight:700; cursor:not-allowed;
    }

    /* Wishlist / Compare */
    .soft-links { display:flex; gap:20px; margin:10px 0 4px; }
    .soft-link {
        font-size:.83rem; color:#679941; text-decoration:none;
        display:inline-flex; align-items:center; gap:4px;
        cursor:pointer; background:none; border:none; padding:0; transition:opacity .2s;
    }
    .soft-link:hover { opacity:.75; text-decoration:none; color:#679941; }

    /* Refund */
    .refund-box {
        display:flex; align-items:center; gap:10px;
        background:#f8fff4; border:1px solid #d4edca;
        border-radius:6px; padding:8px 14px;
    }
    .refund-box i { color:#679941; font-size:1.3rem; flex-shrink:0; }
    .refund-text { font-size:.78rem; color:#555; line-height:1.35; }
    .refund-text strong { display:block; font-size:.8rem; color:#333; }
    .refund-box a { font-size:.78rem; color:#679941; text-decoration:none; margin-left:auto; white-space:nowrap; }
    .refund-box a:hover { text-decoration:underline; }

    /* Share */
    .share-row { display:flex; align-items:center; gap:8px; }
    .share-btn {
        width:30px; height:30px; border-radius:4px;
        display:inline-flex; align-items:center; justify-content:center;
        color:#fff; font-size:.82rem; text-decoration:none; transition:opacity .2s;
    }
    .share-btn:hover { opacity:.82; color:#fff; text-decoration:none; }
    .share-email   { background:#777; }
    .share-twitter { background:#1da1f2; }
    .share-fb      { background:#3b5998; }
    .share-li      { background:#0077b5; }
    .share-wa      { background:#25d366; }

    /* ── Tabs ── */
    .tabs-wrap { background:#fff; box-shadow:0 1px 4px rgba(0,0,0,.07); margin-bottom:16px; border-radius:4px; overflow:hidden; }
    .tabs-bar { display:flex; border-bottom:2px solid #eee; }
    .tab-btn {
        padding:12px 22px; font-size:.85rem; font-weight:600; color:#888;
        border:none; background:none; cursor:pointer;
        border-bottom:3px solid transparent; margin-bottom:-2px; transition:color .2s;
    }
    .tab-btn.active { color:#679941; border-bottom-color:#679941; }
    .tab-panel { display:none; padding:20px 24px; }
    .tab-panel.active { display:block; }
    .description-text { font-size:.87rem; color:#555; line-height:1.85; }

    /* ── Relacionados ── */
    .related-section { background:#fff; box-shadow:0 1px 4px rgba(0,0,0,.07); padding:20px 24px; border-radius:4px; }
    .related-title { font-size:.95rem; font-weight:700; color:#222; margin-bottom:14px; padding-bottom:10px; border-bottom:2px solid #f0f0f0; }
    .related-grid { display:grid; grid-template-columns:repeat(6,1fr); gap:12px; }
    @media(max-width:1100px){ .related-grid{ grid-template-columns:repeat(4,1fr); } }
    @media(max-width:768px) { .related-grid{ grid-template-columns:repeat(3,1fr); } }
    @media(max-width:480px) { .related-grid{ grid-template-columns:repeat(2,1fr); } }
    .related-card {
        border:1px solid #eee; border-radius:4px; overflow:hidden;
        text-decoration:none; color:inherit; display:block; transition:box-shadow .2s, transform .2s;
    }
    .related-card:hover { box-shadow:0 4px 14px rgba(0,0,0,.1); transform:translateY(-2px); text-decoration:none; }
    .related-card img { width:100%; aspect-ratio:1/1; object-fit:cover; background:#f5f5f5; display:block; }
    .related-card-body { padding:8px 10px 10px; }
    .related-card-price { font-size:.85rem; font-weight:700; color:#679941; }
    .related-card-name  { font-size:.75rem; color:#666; line-height:1.3; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
</style>
@endsection

@section('content')
<div class="product-page">
    <div class="container">

        {{-- Breadcrumb --}}
        <div class="breadcrumb-bar">
            <a href="{{ url('/') }}">Home</a>
            <span>/</span>
            @if($product->category)
                <a href="{{ route('category.show', $product->category->slug) }}">{{ $product->category->name }}</a>
                <span>/</span>
            @endif
            <strong style="color:#555;">{{ Str::limit($product->name, 60) }}</strong>
        </div>

        {{-- ════ BLOQUE PRINCIPAL ════ --}}
        <div class="product-main">
            <div class="row">

                {{-- ── GALERÍA ── --}}
                <div class="col-lg-5 col-md-6">
                    <div class="gallery-col">

                        {{-- Thumbs verticales --}}
                        <div class="gallery-thumbs-vert">
                            @if($product->thumbnail)
                                <div class="gallery-thumb active"
                                     onclick="setMainImg('{{ asset('storage/' . $product->thumbnail) }}', this)">
                                    <img src="{{ asset('storage/' . $product->thumbnail) }}" alt="main">
                                </div>
                            @endif
                            @foreach($photos as $photo)
                                <div class="gallery-thumb"
                                     onclick="setMainImg('{{ asset('storage/' . $photo) }}', this)">
                                    <img src="{{ asset('storage/' . $photo) }}" alt="photo">
                                </div>
                            @endforeach
                        </div>

                        {{-- Imagen principal --}}
                        <div class="gallery-main-wrap">
                            @if($product->thumbnail)
                                <img src="{{ asset('storage/' . $product->thumbnail) }}"
                                     alt="{{ $product->name }}" id="main-img">
                            @else
                                <i class="las la-image" style="font-size:5rem;color:#ddd;"></i>
                            @endif
                        </div>

                    </div>
                </div>

                {{-- ── INFO ── --}}
                <div class="col-lg-7 col-md-6">
                    <div class="info-panel">

                        {{-- Título --}}
                        <h1 class="product-title">{{ $product->name }}</h1>

                        {{-- Estrellas --}}
                        <div class="stars-row">
                            <div class="stars">
                                @for($s=1;$s<=5;$s++)
                                    <i class="{{ $s<=round($product->rating)?'las filled':'lar' }} la-star"></i>
                                @endfor
                            </div>
                            <span class="reviews-count">({{ $product->reviews_count }} reviews)</span>
                        </div>

                        {{-- Sold by --}}
                        @if(isset($product->addedBy) && $product->addedBy)
                        <div class="info-row">
                            <span class="info-label">Sold by:</span>
                            <div class="info-value seller-row">
                                <span class="seller-name">{{ $product->addedBy->name }}</span>
                                <a href="#" class="btn-msg-seller">
                                    <i class="las la-comment-dots"></i> Message Seller
                                </a>
                            </div>
                        </div>
                        @endif

                        {{-- Precio --}}
                        @php
                            $finalPrice = $product->unit_price;
                            if ($product->discount > 0) {
                                $finalPrice = $product->discount_type === 'percent'
                                    ? $product->unit_price * (1 - $product->discount / 100)
                                    : $product->unit_price - $product->discount;
                            }
                            $totalStock = $product->stocks->sum('qty');

                            // Detectar tallas (texto) vs colores (hex o nombres de colores)
                            $colorNames = ['red','green','blue','black','white','yellow','pink','purple','orange','gray','grey','brown','navy','maroon','rojo','negro','blanco','azul','verde','amarillo','rosa','morado','naranja','gris','café'];
                            $sizeVariants  = $product->stocks->filter(function($s) use ($colorNames) {
                                if (!$s->variant) return false;
                                $v = strtolower(trim($s->variant));
                                return !str_starts_with($v, '#') && !in_array($v, $colorNames);
                            })->values();
                            $colorVariants = $product->stocks->filter(function($s) use ($colorNames) {
                                if (!$s->variant) return false;
                                $v = strtolower(trim($s->variant));
                                return str_starts_with($v, '#') || in_array($v, $colorNames);
                            })->values();

                            $colorMap = [
                                'red'=>'#e74c3c','rojo'=>'#e74c3c',
                                'green'=>'#27ae60','verde'=>'#27ae60',
                                'blue'=>'#2980b9','azul'=>'#2980b9',
                                'black'=>'#222','negro'=>'#222',
                                'white'=>'#fff','blanco'=>'#fff',
                                'yellow'=>'#f1c40f','amarillo'=>'#f1c40f',
                                'pink'=>'#e91e8c','rosa'=>'#e91e8c',
                                'purple'=>'#9b59b6','morado'=>'#9b59b6',
                                'orange'=>'#e67e22','naranja'=>'#e67e22',
                                'gray'=>'#95a5a6','grey'=>'#95a5a6','gris'=>'#95a5a6',
                                'brown'=>'#795548','café'=>'#795548',
                                'navy'=>'#1a237e','maroon'=>'#880e4f',
                            ];
                        @endphp

                        <div class="info-row">
                            <span class="info-label">Price:</span>
                            <div class="info-value" style="display:flex;align-items:baseline;flex-wrap:wrap;gap:4px;">
                                <span class="price-big" id="shown-price">
                                    ${{ number_format($finalPrice, 2) }}
                                </span>
                                @if($product->unit)
                                    <span class="price-unit">/{{ $product->unit }}</span>
                                @endif
                                @if($product->discount > 0)
                                    <span class="price-original">${{ number_format($product->unit_price, 2) }}</span>
                                    <span class="discount-pill">-{{ $product->discount }}{{ $product->discount_type==='percent'?'%':'$' }}</span>
                                @endif
                            </div>
                        </div>

                        <form id="option-choice-form">
                            @csrf
                            <input type="hidden" name="id" value="{{ $product->id }}">

                            {{-- TALLAS --}}
                            @if($sizeVariants->count() > 0)
                            <div class="info-row">
                                <span class="info-label">Size:</span>
                                <div class="info-value">
                                    <div class="size-chips">
                                        @foreach($sizeVariants as $stock)
                                            <label style="cursor:pointer;margin:0;" title="{{ $stock->variant }}">
                                                <input type="radio" name="variant" value="{{ $stock->variant }}"
                                                       style="display:none;" onchange="onVariantChange(this)">
                                                <span class="size-chip {{ $stock->qty<=0?'no-stock':'' }}"
                                                      data-variant="{{ $stock->variant }}">
                                                    {{ strtoupper($stock->variant) }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endif

                            {{-- COLORES --}}
                            @if($colorVariants->count() > 0)
                            <div class="info-row">
                                <span class="info-label">Color:</span>
                                <div class="info-value">
                                    <div class="color-chips">
                                        @foreach($colorVariants as $stock)
                                            @php
                                                $v   = strtolower(trim($stock->variant));
                                                $hex = str_starts_with($v,'#') ? $v : ($colorMap[$v] ?? '#888');
                                            @endphp
                                            <label style="cursor:pointer;margin:0;" title="{{ $stock->variant }}">
                                                <input type="radio" name="color_variant" value="{{ $stock->variant }}"
                                                       style="display:none;" onchange="onVariantChange(this)">
                                                <span class="color-chip"
                                                      style="background:{{ $hex }};{{ $hex==='#fff'?'border:1px solid #ccc;':'' }}"
                                                      data-variant="{{ $stock->variant }}">
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endif

                            {{-- CANTIDAD --}}
                            <div class="info-row">
                                <span class="info-label">Quantity:</span>
                                <div class="info-value">
                                    <div class="qty-row">
                                        <div class="qty-control">
                                            <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
                                            <input type="number" name="quantity" id="qty-input"
                                                   class="qty-input" value="1" min="1" max="{{ $totalStock }}">
                                            <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                                        </div>
                                        <span class="stock-available" id="stock-label">
                                            ({{ $totalStock }} available)
                                        </span>
                                    </div>
                                </div>
                            </div>

                        </form>

                        {{-- Precio variante seleccionada --}}
                        <div id="chosen_price_div" style="display:none; padding:6px 0 2px;">
                            <span style="font-size:.8rem;color:#888;">Selected price:</span>
                            <strong id="chosen_price" style="color:#679941;font-size:1rem;margin-left:6px;"></strong>
                        </div>

                        {{-- CTA --}}
                        <div class="cta-row">
                            @if($totalStock > 0)
                                <button type="button" class="btn-addcart add-to-cart" onclick="addToCart()">
                                    <i class="las la-shopping-cart"></i> Add to cart
                                </button>
                                <button type="button" class="btn-buynow buy-now" onclick="addToCart()">
                                    <i class="las la-bolt"></i> Buy Now
                                </button>
                            @else
                                <button type="button" class="btn-outstock" disabled>Out of Stock</button>
                            @endif
                        </div>

                        {{-- Wishlist / Compare --}}
                        <div class="soft-links">
                            <button class="soft-link" onclick="addToWishList({{ $product->id }})">
                                <i class="las la-heart"></i> Add to wishlist
                            </button>
                            <button class="soft-link" onclick="addToCompare({{ $product->id }})">
                                <i class="las la-exchange-alt"></i> Add to compare
                            </button>
                        </div>

                        {{-- Refund --}}
                        <div class="info-row">
                            <span class="info-label">Refund:</span>
                            <div class="info-value">
                                <div class="refund-box">
                                    <i class="las la-shield-alt"></i>
                                    <div class="refund-text">
                                        <strong>Active eCommerce Refund Protection</strong>
                                        30 Days Cash Back Guarantee
                                    </div>
                                    <a href="{{ route('return-policy') }}">View Policy</a>
                                </div>
                            </div>
                        </div>

                        {{-- Share --}}
                        <div class="info-row">
                            <span class="info-label">Share:</span>
                            <div class="info-value">
                                <div class="share-row">
                                    @php
                                        $shareUrl   = urlencode(url('/product/' . $product->slug));
                                        $shareTitle = urlencode($product->name);
                                    @endphp
                                    <a href="mailto:?subject={{ $shareTitle }}&body={{ $shareUrl }}"
                                       class="share-btn share-email" title="Email">
                                        <i class="las la-envelope"></i>
                                    </a>
                                    <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareTitle }}"
                                       target="_blank" class="share-btn share-twitter" title="Twitter">
                                        <i class="lab la-twitter"></i>
                                    </a>
                                    <a href="https://facebook.com/sharer/sharer.php?u={{ $shareUrl }}"
                                       target="_blank" class="share-btn share-fb" title="Facebook">
                                        <i class="lab la-facebook-f"></i>
                                    </a>
                                    <a href="https://linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}"
                                       target="_blank" class="share-btn share-li" title="LinkedIn">
                                        <i class="lab la-linkedin-in"></i>
                                    </a>
                                    <a href="https://api.whatsapp.com/send?text={{ $shareTitle }}%20{{ $shareUrl }}"
                                       target="_blank" class="share-btn share-wa" title="WhatsApp">
                                        <i class="lab la-whatsapp"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>{{-- /info-panel --}}
                </div>

            </div>
        </div>{{-- /product-main --}}

        {{-- ════ Descripción / Reseñas ════ --}}
        <div class="tabs-wrap">
            <div class="tabs-bar">
                <button class="tab-btn active" onclick="switchTab('desc',this)">Description</button>
                <button class="tab-btn" onclick="switchTab('reviews',this)">
                    Reviews ({{ $product->reviews_count }})
                </button>
            </div>
            <div class="tab-panel active" id="tab-desc">
                @if($product->description)
                    <div class="description-text">{!! nl2br(e($product->description)) !!}</div>
                @else
                    <p style="color:#bbb;font-size:.85rem;">No description available.</p>
                @endif
            </div>
            <div class="tab-panel" id="tab-reviews">
                @if($product->reviews && $product->reviews->count() > 0)
                    @foreach($product->reviews as $review)
                        <div style="border-bottom:1px solid #f0f0f0;padding:12px 0;">
                            <div style="font-size:.83rem;font-weight:600;color:#333;margin-bottom:4px;">
                                {{ $review->user->name ?? 'User' }}
                                <span style="color:#f0ad00;margin-left:8px;">
                                    @for($s=1;$s<=5;$s++)
                                        <i class="{{ $s<=$review->rating?'las':'lar' }} la-star" style="font-size:.75rem;"></i>
                                    @endfor
                                </span>
                            </div>
                            <p style="font-size:.83rem;color:#666;margin:0;">{{ $review->comment }}</p>
                        </div>
                    @endforeach
                @else
                    <p style="color:#bbb;font-size:.85rem;">No reviews yet.</p>
                @endif
            </div>
        </div>

        {{-- ════ Relacionados ════ --}}
        @if($related->count() > 0)
        <div class="related-section">
            <h3 class="related-title">Related Products</h3>
            <div class="related-grid">
                @foreach($related as $rel)
                    <a href="{{ url('/product/' . $rel->slug) }}" class="related-card">
                        @if($rel->thumbnail)
                            <img src="{{ asset('storage/' . $rel->thumbnail) }}" alt="{{ $rel->name }}">
                        @else
                            <div style="width:100%;aspect-ratio:1/1;background:#f0f0f0;display:flex;align-items:center;justify-content:center;">
                                <i class="las la-image" style="font-size:2rem;color:#ccc;"></i>
                            </div>
                        @endif
                        <div class="related-card-body">
                            <div class="related-card-price">${{ number_format($rel->unit_price, 2) }}</div>
                            <div class="related-card-name">{{ $rel->name }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>
@endsection

@section('extra_js')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ── Toast notification ────────────────────────────────────────────
function showToast(msg, type = 'success') {
    let toast = document.getElementById('aiz-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'aiz-toast';
        toast.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;padding:12px 20px;border-radius:8px;font-size:.85rem;font-weight:600;color:#fff;opacity:0;transform:translateY(10px);transition:all .3s;pointer-events:none;';
        document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.style.background = type === 'success' ? '#679941' : '#e74c3c';
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
    }, 3000);
}

// ── Cambiar imagen principal ──────────────────────────────────────
function setMainImg(src, thumb) {
    document.getElementById('main-img').src = src;
    document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
}

// ── Control cantidad ──────────────────────────────────────────────
function changeQty(delta) {
    const input = document.getElementById('qty-input');
    const max   = parseInt(input.max) || 999;
    let val = parseInt(input.value) + delta;
    if (val < 1)   val = 1;
    if (val > max) val = max;
    input.value = val;
}

// ── Selección de variante ─────────────────────────────────────────
function onVariantChange(radio) {
    const name = radio.name;
    document.querySelectorAll(`input[name="${name}"]`).forEach(r => {
        const chip = r.parentElement.querySelector('.size-chip, .color-chip');
        if (chip) chip.classList.remove('selected');
    });
    const myChip = radio.parentElement.querySelector('.size-chip, .color-chip');
    if (myChip) myChip.classList.add('selected');
    getVariantPrice();
}

// ── Precio por variante ───────────────────────────────────────────
function getVariantPrice() {
    // Recoger variantes seleccionadas
    const variants = {};
    document.querySelectorAll('input[type="radio"]:checked').forEach(r => {
        variants[r.name] = r.value;
    });

    const variantStr = Object.values(variants).join('-');
    if (!variantStr) return;

    fetch('{{ route("products.variant_price") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ product_id: {{ $product->id }}, variant: variantStr })
    })
    .then(r => r.json())
    .then(data => {
        if (data.price) {
            document.getElementById('chosen_price_div').style.display = 'block';
            document.getElementById('chosen_price').textContent = '$' + data.price;
            if (data.stock !== undefined) {
                document.getElementById('qty-input').max = data.stock;
                document.getElementById('stock-label').textContent = '(' + data.stock + ' available)';
            }
        }
    })
    .catch(() => {});
}

// ── ADD TO CART ───────────────────────────────────────────────────
function addToCart(buyNow = false) {
    const productId = {{ $product->id }};
    const quantity  = parseInt(document.getElementById('qty-input')?.value || 1);

    // Recoger variante seleccionada
    let variation = null;
    const variantParts = [];
    document.querySelectorAll('input[type="radio"]:checked').forEach(r => {
        variantParts.push(r.value);
    });
    if (variantParts.length) variation = variantParts.join('-');

    // Deshabilitar botones mientras carga
    document.querySelectorAll('.btn-addcart, .btn-buynow').forEach(btn => {
        btn.disabled = true;
    });

    fetch('{{ route("cart.add") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ product_id: productId, quantity, variation })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            // Actualizar contador del carrito en el header
            document.querySelectorAll('.cart-count, .cart-count-badge').forEach(el => {
                el.textContent = data.cart_count;
            });

            showToast('✓ Added to cart!');

            if (buyNow) {
                window.location.href = '{{ url("/cart") }}';
            }
        } else {
            showToast(data.message || 'Error adding to cart', 'error');
        }
    })
    .catch(() => {
        showToast('Connection error. Please try again.', 'error');
    })
    .finally(() => {
        document.querySelectorAll('.btn-addcart, .btn-buynow').forEach(btn => {
            btn.disabled = false;
        });
    });
}

// ── ADD TO WISHLIST ───────────────────────────────────────────────
function addToWishList(productId) {
    @auth
    fetch('{{ url("/wishlist/add") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ product_id: productId })
    })
    .then(r => r.json())
    .then(data => {
        showToast(data.message || 'Added to wishlist!');
    })
    .catch(() => showToast('Error adding to wishlist', 'error'));
    @else
    showToast('Please login to add to wishlist', 'error');
    setTimeout(() => window.location.href = '{{ url("/login") }}', 1500);
    @endauth
}

// ── ADD TO COMPARE ────────────────────────────────────────────────
function addToCompare(productId) {
    fetch('{{ url("/compare/add") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ product_id: productId })
    })
    .then(r => r.json())
    .then(data => {
        showToast(data.message || 'Added to compare!');
    })
    .catch(() => showToast('Error', 'error'));
}

// ── Tabs ──────────────────────────────────────────────────────────
function switchTab(id, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + id).classList.add('active');
    btn.classList.add('active');
}
</script>
@endsection