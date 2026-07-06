@extends('layouts.app')

@section('title', $category->name)
@section('meta_description', $category->description ?? $category->name . ' products')

@section('extra_css')
<style>
    body { background: #f5f6f8; }

    /* ── Layout ── */
    .cat-page { padding: 24px 0 40px; }

    /* ── Breadcrumb ── */
    .cat-breadcrumb {
        font-size: .82rem;
        color: #888;
        margin-bottom: 14px;
    }
    .cat-breadcrumb a { color: #679941; text-decoration: none; }
    .cat-breadcrumb a:hover { text-decoration: underline; }
    .cat-breadcrumb span { margin: 0 5px; color: #bbb; }

    /* ── Sidebar ── */
    .sidebar-card {
        background: #fff;
        border-radius: 8px;
        border: 1px solid #e8eaed;
        margin-bottom: 16px;
        overflow: hidden;
    }
    .sidebar-card-header {
        padding: 12px 16px;
        font-size: .85rem;
        font-weight: 700;
        color: #333;
        border-bottom: 1px solid #f0f0f0;
    }
    .sidebar-card-body { padding: 12px 16px; }

    /* Category links */
    .cat-link {
        display: flex;
        align-items: center;
        padding: 6px 8px;
        border-radius: 5px;
        font-size: .83rem;
        color: #444;
        text-decoration: none;
        transition: background .15s, color .15s;
    }
    .cat-link:hover, .cat-link.active {
        background: rgba(103,153,65,.1);
        color: #679941;
        text-decoration: none;
    }
    .cat-link .dot {
        width: 6px; height: 6px;
        border-radius: 50%;
        background: #ccc;
        margin-right: 8px;
        flex-shrink: 0;
    }
    .cat-link.active .dot { background: #679941; }

    /* Price range slider */
    .price-slider-wrap { padding: 4px 0 8px; }
    input[type=range] {
        width: 100%;
        accent-color: #679941;
        cursor: pointer;
    }
    .price-labels {
        display: flex;
        justify-content: space-between;
        font-size: .78rem;
        color: #666;
        margin-top: 4px;
    }

    /* ── Top bar (results + sort) ── */
    .top-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 16px;
    }
    .top-bar-left { font-size: .83rem; color: #888; }
    .top-bar-right { display: flex; align-items: center; gap: 10px; }
    .sort-select {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 5px 28px 5px 10px;
        font-size: .83rem;
        color: #333;
        background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 10 10'%3E%3Cpath fill='%23888' d='M5 7L0 2h10z'/%3E%3C/svg%3E") no-repeat right 10px center;
        appearance: none;
        cursor: pointer;
        outline: none;
    }
    .sort-select:focus { border-color: #679941; }

    /* ── Product grid ── */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
    }
    @media (max-width: 1100px) { .products-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 768px)  { .products-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 480px)  { .products-grid { grid-template-columns: repeat(1, 1fr); } }

    /* ── Product card ── */
    .product-card {
        background: #fff;
        border-radius: 8px;
        border: 1px solid #e8eaed;
        overflow: hidden;
        transition: box-shadow .2s, transform .2s;
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .product-card:hover {
        box-shadow: 0 4px 18px rgba(0,0,0,.12);
        transform: translateY(-2px);
        text-decoration: none;
        color: inherit;
    }
    .product-card-img {
        width: 100%;
        aspect-ratio: 1 / 1;
        object-fit: cover;
        background: #f0f0f0;
        display: block;
    }
    .product-card-img-placeholder {
        width: 100%;
        aspect-ratio: 1 / 1;
        background: #e8eaed;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .product-card-img-placeholder i { font-size: 2.5rem; color: #bbb; }
    .product-card-body { padding: 10px 12px 12px; }
    .product-card-price {
        font-size: .95rem;
        font-weight: 700;
        color: #e74c3c;
        margin-bottom: 4px;
    }
    .product-card-name {
        font-size: .78rem;
        color: #555;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .product-card-stars { color: #f0ad00; font-size: .72rem; margin-bottom: 2px; }

    /* ── Paginación ── */
    .pagination-wrap {
        display: flex;
        justify-content: center;
        margin-top: 28px;
        gap: 4px;
        flex-wrap: wrap;
    }
    .page-btn {
        min-width: 32px; height: 32px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background: #fff;
        color: #555;
        font-size: .82rem;
        font-weight: 600;
        display: flex; align-items: center; justify-content: center;
        text-decoration: none;
        transition: background .15s, border-color .15s, color .15s;
        padding: 0 8px;
    }
    .page-btn:hover { border-color: #679941; color: #679941; text-decoration: none; }
    .page-btn.active {
        background: #679941;
        border-color: #679941;
        color: #fff;
    }
    .page-btn.disabled { opacity: .4; pointer-events: none; }

    /* ── Empty state ── */
    .empty-state { text-align: center; padding: 4rem 2rem; color: #aaa; }
    .empty-state i { font-size: 3rem; display: block; margin-bottom: 1rem; }
</style>
@endsection

@section('content')
<div class="cat-page">
    <div class="container">

        {{-- Breadcrumb --}}
        <div class="cat-breadcrumb">
            <a href="{{ url('/') }}">Home</a>
            <span>/</span>
            <a href="{{ url('/categories') }}">All Categories</a>
            <span>/</span>
            <strong style="color:#333;">{{ $category->name }}</strong>
        </div>

        <div class="row gutters-10">

            {{-- ══════════════════════════════════
                 SIDEBAR
            ══════════════════════════════════ --}}
            <div class="col-lg-2 col-md-3 mb-4">

                {{-- Categorías --}}
                <div class="sidebar-card">
                    <div class="sidebar-card-header">Categories</div>
                    <div class="sidebar-card-body p-2">
                        <a href="{{ url('/categories') }}"
                           class="cat-link {{ !request('cat') ? 'active' : '' }}">
                            <span class="dot"></span> All categories
                        </a>
                        @foreach($allCategories as $cat)
                            <a href="{{ route('category.show', $cat->slug) }}"
                               class="cat-link {{ $category->id === $cat->id ? 'active' : '' }}">
                                <span class="dot"></span>
                                {{ $cat->name }}
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Rango de precio --}}
                <div class="sidebar-card">
                    <div class="sidebar-card-header">Price range</div>
                    <div class="sidebar-card-body">
                        <form method="GET" action="{{ route('category.show', $category->slug) }}" id="price-form">
                            @foreach(request()->except(['min_price','max_price','page']) as $k => $v)
                                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                            @endforeach

                            <div class="price-slider-wrap">
                                <input type="range"
                                       name="max_price"
                                       id="price-range"
                                       min="0"
                                       max="{{ ceil($maxPrice) }}"
                                       value="{{ request('max_price', ceil($maxPrice)) }}"
                                       oninput="document.getElementById('price-val').textContent = this.value; this.form.submit();">
                                <div class="price-labels">
                                    <span>$0.00</span>
                                    <span id="price-val">${{ number_format(request('max_price', $maxPrice), 2) }}</span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
            {{-- /sidebar --}}

            {{-- ══════════════════════════════════
                 CONTENIDO PRINCIPAL
            ══════════════════════════════════ --}}
            <div class="col-lg-10 col-md-9">

                {{-- Título + top bar --}}
                <h1 style="font-size:1.1rem; font-weight:700; color:#222; margin-bottom:14px;">
                    {{ $category->name }}
                </h1>

                <div class="top-bar">
                    <div class="top-bar-left">
                        {{ $products->total() }} result(s) found
                    </div>
                    <div class="top-bar-right">
                        {{-- Selector de resultados por página --}}
                        <form method="GET" action="{{ route('category.show', $category->slug) }}" style="display:inline;">
                            @foreach(request()->except(['per_page','page']) as $k => $v)
                                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                            @endforeach
                            <select name="per_page" class="sort-select" onchange="this.form.submit()">
                                @foreach([12, 24, 48] as $n)
                                    <option value="{{ $n }}" {{ request('per_page', 12) == $n ? 'selected' : '' }}>
                                        {{ $n }} Results
                                    </option>
                                @endforeach
                            </select>
                        </form>

                        {{-- Ordenamiento --}}
                        <form method="GET" action="{{ route('category.show', $category->slug) }}" style="display:inline;">
                            @foreach(request()->except(['sort','page']) as $k => $v)
                                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                            @endforeach
                            <select name="sort" class="sort-select" onchange="this.form.submit()">
                                <option value="newest"     {{ request('sort','newest')=='newest'     ? 'selected':'' }}>Newest</option>
                                <option value="popular"    {{ request('sort')=='popular'    ? 'selected':'' }}>Most Popular</option>
                                <option value="price_asc"  {{ request('sort')=='price_asc'  ? 'selected':'' }}>Price: Low to High</option>
                                <option value="price_desc" {{ request('sort')=='price_desc' ? 'selected':'' }}>Price: High to Low</option>
                            </select>
                        </form>
                    </div>
                </div>

                {{-- Grid de productos --}}
                @if($products->count() > 0)
                    <div class="products-grid">
                        @foreach($products as $product)
                            <a href="{{ url('/product/' . $product->slug) }}" class="product-card">
                                {{-- Imagen --}}
                                @if($product->thumbnail)
                                    <img src="{{ asset('storage/' . $product->thumbnail) }}"
                                         alt="{{ $product->name }}"
                                         class="product-card-img"
                                         loading="lazy">
                                @else
                                    <div class="product-card-img-placeholder">
                                        <i class="las la-image"></i>
                                    </div>
                                @endif

                                <div class="product-card-body">
                                    {{-- Estrellas --}}
                                    <div class="product-card-stars">
                                        @for($s = 1; $s <= 5; $s++)
                                            @if($s <= round($product->rating))
                                                <i class="las la-star"></i>
                                            @else
                                                <i class="lar la-star"></i>
                                            @endif
                                        @endfor
                                    </div>

                                    {{-- Precio --}}
                                    <div class="product-card-price">
                                        @if($product->discount > 0)
                                            @php
                                                $finalPrice = $product->discount_type === 'percent'
                                                    ? $product->unit_price * (1 - $product->discount / 100)
                                                    : $product->unit_price - $product->discount;
                                            @endphp
                                            ${{ number_format($finalPrice, 2) }}
                                            <small style="text-decoration:line-through; color:#bbb; font-weight:400; font-size:.75rem;">
                                                ${{ number_format($product->unit_price, 2) }}
                                            </small>
                                        @else
                                            ${{ number_format($product->unit_price, 2) }}
                                        @endif
                                    </div>

                                    {{-- Nombre --}}
                                    <div class="product-card-name">{{ $product->name }}</div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    {{-- Paginación --}}
                    @if($products->hasPages())
                        <div class="pagination-wrap">
                            {{-- Anterior --}}
                            @if($products->onFirstPage())
                                <span class="page-btn disabled">&laquo;</span>
                            @else
                                <a href="{{ $products->previousPageUrl() }}" class="page-btn">&laquo;</a>
                            @endif

                            {{-- Páginas --}}
                            @foreach($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                                @if($page == $products->currentPage())
                                    <span class="page-btn active">{{ $page }}</span>
                                @elseif(
                                    $page == 1 ||
                                    $page == $products->lastPage() ||
                                    abs($page - $products->currentPage()) <= 2
                                )
                                    <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
                                @elseif(abs($page - $products->currentPage()) == 3)
                                    <span class="page-btn disabled">…</span>
                                @endif
                            @endforeach

                            {{-- Siguiente --}}
                            @if($products->hasMorePages())
                                <a href="{{ $products->nextPageUrl() }}" class="page-btn">&raquo;</a>
                            @else
                                <span class="page-btn disabled">&raquo;</span>
                            @endif
                        </div>
                    @endif

                @else
                    <div class="empty-state">
                        <i class="las la-box-open"></i>
                        <p style="font-size:.95rem; font-weight:600; color:#888;">No products found in this category.</p>
                        <a href="{{ url('/') }}" style="color:#679941; font-size:.85rem;">← Back to Home</a>
                    </div>
                @endif

            </div>
            {{-- /main content --}}

        </div>
    </div>
</div>
@endsection