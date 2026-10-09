@extends('layouts.app')

@section('title', __('Mis productos'))

@section('extra_css')
<style>
    body { background: #f5f6f8; }
    .my-products-page { padding: 24px 0 50px; }

    .top-bar {
        display: flex; align-items: center;
        justify-content: space-between;
        flex-wrap: wrap; gap: 10px;
        margin-bottom: 20px;
    }
    .top-bar h1 { font-size: 1.1rem; font-weight: 700; color: #222; margin: 0; }
    .top-bar h1 span { color: #aaa; font-size: .85rem; font-weight: 400; }
    .btn-go-cart {
        background: #679941; color: #fff; border-radius: 6px;
        padding: 8px 16px; font-size: .85rem; font-weight: 600;
        text-decoration: none; transition: background .2s;
    }
    .btn-go-cart:hover { background: #4e7a2e; color: #fff; text-decoration: none; }

    /* ── Grid (mismas columnas que /products) ── */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
    }
    @media (max-width: 1100px) { .products-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 768px)  { .products-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; } }

    .my-card {
        background: #fff; border-radius: 8px; border: 1px solid #e8eaed;
        overflow: hidden; display: flex; flex-direction: column;
        transition: box-shadow .2s;
    }
    .my-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,.12); }
    .my-card-img {
        width: 100%; aspect-ratio: 1/1; object-fit: cover;
        background: #f0f0f0; display: block;
    }
    .my-card-body { padding: 10px 12px 12px; display: flex; flex-direction: column; gap: 6px; flex: 1; }
    .my-card-name {
        font-size: .82rem; color: #333; line-height: 1.4; font-weight: 600;
        display: -webkit-box; -webkit-line-clamp: 2;
        -webkit-box-orient: vertical; overflow: hidden;
        text-decoration: none;
    }
    .my-card-name:hover { color: #679941; text-decoration: none; }
    .my-card-lines { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 4px; }
    .my-card-lines li { display: flex; align-items: center; justify-content: space-between; gap: 6px; font-size: .75rem; color: #777; }
    .my-card-foot {
        margin-top: auto; display: flex; align-items: baseline; justify-content: space-between;
        border-top: 1px solid #f0f0f0; padding-top: 6px;
    }
    .my-card-qty { font-size: .75rem; color: #999; }
    .my-card-total { font-size: .95rem; font-weight: 700; color: #e74c3c; }

    .empty-state { text-align: center; padding: 4rem 2rem; color: #aaa; }
    .empty-state i { font-size: 3rem; display: block; margin-bottom: 1rem; }
</style>
@endsection

@section('content')
<div class="my-products-page">
    <div class="container">

        <div class="top-bar">
            <h1>{{ __('Mis productos') }} <span>({{ $items->count() }})</span></h1>
            @if($items->isNotEmpty())
                <a href="{{ route('cart.index') }}" class="btn-go-cart">
                    <i class="las la-shopping-cart"></i> {{ __('Ir al carrito') }}
                </a>
            @endif
        </div>

        @if($items->isNotEmpty())
            <div class="products-grid">
                @foreach($items as $item)
                    @php($product = $item['product'])
                    <div class="my-card">
                        <a href="{{ route('products.show', $product->slug) }}">
                            <img src="{{ uploaded_asset($product->thumbnail) }}"
                                 alt="{{ $product->name }}" class="my-card-img" loading="lazy"
                                 onerror="this.onerror=null;this.src='{{ asset('assets/img/placeholder.jpg') }}';">
                        </a>
                        <div class="my-card-body">
                            <a href="{{ route('products.show', $product->slug) }}" class="my-card-name">{{ $product->name }}</a>

                            @if($item['lines']->contains(fn ($line) => $line->hasVariant()))
                                <ul class="my-card-lines">
                                    @foreach($item['lines'] as $line)
                                        <li>
                                            @include('partials.variant-badge', ['parts' => $line->variant_parts, 'small' => true, 'fallback' => '—'])
                                            <span>× {{ $line->quantity }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            <div class="my-card-foot">
                                <span class="my-card-qty">{{ trans_choice(':count unidad|:count unidades', $item['quantity']) }}</span>
                                <span class="my-card-total">${{ number_format($item['subtotal'], 2) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <i class="las la-shopping-basket"></i>
                <p style="font-size:.95rem; font-weight:600; color:#888;">
                    {{ __('Todavía no agregaste productos. Lo que añadas al carrito aparecerá aquí.') }}
                </p>
                <a href="{{ route('products.index') }}" style="color:#679941; font-size:.85rem;">{{ __('Explorar productos') }} →</a>
            </div>
        @endif

    </div>
</div>
@endsection
