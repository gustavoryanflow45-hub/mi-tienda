@extends('layouts.app')

@section('title', 'Search: ' . $keyword)

@section('extra_css')
<style>
    body { background: #f5f6f8; }
    .search-page { padding: 24px 0 50px; }
    .product-card {
        background: #fff; border-radius: 8px; border: 1px solid #e8eaed;
        overflow: hidden; transition: box-shadow .2s, transform .2s;
        text-decoration: none; color: inherit; display: block;
    }
    .product-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,.12); transform: translateY(-2px); text-decoration: none; color: inherit; }
    .product-card-placeholder { width:100%; aspect-ratio:1/1; background:#e8eaed; display:flex; align-items:center; justify-content:center; }
    .product-card-placeholder i { font-size: 2.5rem; color: #bbb; }
    .product-card-img { width:100%; aspect-ratio:1/1; object-fit:cover; background:#f0f0f0; display:block; }
    .product-card-body { padding: 10px 12px 12px; }
    .product-card-stars { color: #f0ad00; font-size: .72rem; margin-bottom: 3px; }
    .product-card-price { font-size: .95rem; font-weight: 700; color: #e74c3c; margin-bottom: 4px; }
    .product-card-price .original { font-size: .75rem; color: #bbb; text-decoration: line-through; font-weight: 400; margin-left: 4px; }
    .product-card-name { font-size: .78rem; color: #555; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .products-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
    @media (max-width: 1100px) { .products-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 768px)  { .products-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 480px)  { .products-grid { grid-template-columns: 1fr; } }
    .pagination-wrap { display:flex; justify-content:center; margin-top:28px; gap:4px; flex-wrap:wrap; }
    .page-btn { min-width:32px; height:32px; border:1px solid #ddd; border-radius:5px; background:#fff; color:#555; font-size:.82rem; font-weight:600; display:flex; align-items:center; justify-content:center; text-decoration:none; padding:0 8px; transition:all .15s; }
    .page-btn:hover { border-color:#679941; color:#679941; text-decoration:none; }
    .page-btn.active { background:#679941; border-color:#679941; color:#fff; }
    .page-btn.disabled { opacity:.4; pointer-events:none; }
    .empty-state { text-align:center; padding:4rem 2rem; color:#aaa; }
    .empty-state i { font-size:3rem; display:block; margin-bottom:1rem; }
    .search-bar-wrap { display:flex; gap:0; margin-bottom:20px; }
    .search-bar-wrap input { flex:1; border:1px solid #ddd; border-right:none; border-radius:6px 0 0 6px; padding:8px 14px; font-size:.85rem; outline:none; height:38px; }
    .search-bar-wrap input:focus { border-color:#679941; }
    .search-bar-wrap button { background:#679941; color:#fff; border:none; border-radius:0 6px 6px 0; padding:0 18px; font-size:.85rem; cursor:pointer; height:38px; }
    .search-bar-wrap button:hover { background:#4e7a2e; }
</style>
@endsection

@section('content')
<div class="search-page">
    <div class="container">

        {{-- Título --}}
        <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
            <h1 style="font-size:1.1rem; font-weight:700; color:#222; margin:0;">
                @if($keyword)
                    Results for: <span style="color:#679941;">"{{ $keyword }}"</span>
                    <span style="color:#aaa; font-size:.85rem; font-weight:400;">({{ $products->total() }})</span>
                @else
                    All Products
                    <span style="color:#aaa; font-size:.85rem; font-weight:400;">({{ $products->total() }})</span>
                @endif
            </h1>
        </div>

        {{-- Barra de búsqueda --}}
        <form method="GET" action="{{ url('/search') }}" class="search-bar-wrap">
            <input type="text" name="keyword"
                   value="{{ $keyword }}"
                   placeholder="Search products..." autofocus>
            <button type="submit"><i class="las la-search"></i></button>
        </form>

        {{-- Grid de productos --}}
        @if($products->count() > 0)
            <div class="products-grid">
                @foreach($products as $product)
                    @php
                        $finalPrice = $product->unit_price;
                        if ($product->discount > 0) {
                            $finalPrice = $product->discount_type === 'percent'
                                ? $product->unit_price * (1 - $product->discount / 100)
                                : $product->unit_price - $product->discount;
                        }
                    @endphp
                    <a href="{{ url('/product/' . $product->slug) }}" class="product-card">
                        @if($product->thumbnail)
                            <img src="{{ asset('storage/' . $product->thumbnail) }}"
                                 alt="{{ $product->name }}"
                                 class="product-card-img" loading="lazy">
                        @else
                            <div class="product-card-placeholder">
                                <i class="las la-image"></i>
                            </div>
                        @endif
                        <div class="product-card-body">
                            <div class="product-card-stars">
                                @for($s = 1; $s <= 5; $s++)
                                    <i class="{{ $s <= round($product->rating) ? 'las' : 'lar' }} la-star"></i>
                                @endfor
                            </div>
                            <div class="product-card-price">
                                ${{ number_format($finalPrice, 2) }}
                                @if($product->discount > 0)
                                    <span class="original">${{ number_format($product->unit_price, 2) }}</span>
                                @endif
                            </div>
                            <div class="product-card-name">{{ $product->name }}</div>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- Paginación --}}
            @if($products->hasPages())
                <div class="pagination-wrap">
                    @if($products->onFirstPage())
                        <span class="page-btn disabled">&laquo;</span>
                    @else
                        <a href="{{ $products->previousPageUrl() }}" class="page-btn">&laquo;</a>
                    @endif

                    @foreach($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                        @if($page == $products->currentPage())
                            <span class="page-btn active">{{ $page }}</span>
                        @elseif($page == 1 || $page == $products->lastPage() || abs($page - $products->currentPage()) <= 2)
                            <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
                        @elseif(abs($page - $products->currentPage()) == 3)
                            <span class="page-btn disabled">…</span>
                        @endif
                    @endforeach

                    @if($products->hasMorePages())
                        <a href="{{ $products->nextPageUrl() }}" class="page-btn">&raquo;</a>
                    @else
                        <span class="page-btn disabled">&raquo;</span>
                    @endif
                </div>
            @endif

        @else
            <div class="empty-state">
                <i class="las la-search"></i>
                <p style="font-size:.95rem; font-weight:600; color:#888;">
                    @if($keyword)
                        No results found for <strong>"{{ $keyword }}"</strong>
                    @else
                        No products available.
                    @endif
                </p>
                <a href="{{ url('/') }}" style="color:#679941; font-size:.85rem;">← Back to Home</a>
            </div>
        @endif

    </div>
</div>
@endsection