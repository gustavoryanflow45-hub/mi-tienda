{{-- resources/views/home.blade.php --}}
@extends('layouts.app')

@section('title', 'Home')

@section('extra_css')
<style>
/* ── Panel seller/admin ── */
.seller-panel {
    background: linear-gradient(135deg, #f8fdf4, #edf7e4);
    border: 1px solid #c5e0b4;
    border-radius: 12px;
    margin-bottom: 24px;
    overflow: hidden;
}
.seller-panel-header {
    background: linear-gradient(135deg, #679941, #4e7a2e);
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}
.seller-panel-header h4 {
    color: #fff;
    font-size: .95rem;
    font-weight: 700;
    margin: 0;
}
.seller-panel-header .header-actions {
    display: flex;
    gap: 8px;
}
.btn-add-product {
    background: #fff;
    color: #679941;
    border: none;
    border-radius: 6px;
    padding: 6px 14px;
    font-size: .8rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: background .2s;
}
.btn-add-product:hover { background: #f0f8ea; color: #4e7a2e; text-decoration: none; }

.seller-panel-body { padding: 16px 20px 20px; }

/* ── Product management grid ── */
.manage-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
}
.manage-card {
    background: #fff;
    border-radius: 10px;
    border: 1px solid #e0e8d8;
    overflow: hidden;
    transition: box-shadow .2s;
    position: relative;
}
.manage-card:hover { box-shadow: 0 4px 16px rgba(103,153,65,.18); }
.manage-card-img {
    width: 100%; aspect-ratio: 4/3;
    object-fit: cover; background: #f5f5f5; display: block;
}
.manage-card-placeholder {
    width: 100%; aspect-ratio: 4/3;
    background: #f0f0f0;
    display: flex; align-items: center; justify-content: center;
}
.manage-card-placeholder i { font-size: 2rem; color: #ccc; }
.manage-card-body { padding: 10px 12px; }
.manage-card-name {
    font-size: .8rem; font-weight: 600; color: #333;
    display: -webkit-box; -webkit-line-clamp: 2;
    -webkit-box-orient: vertical; overflow: hidden;
    margin-bottom: 6px; line-height: 1.3;
}
.manage-card-price { font-size: .85rem; font-weight: 700; color: #679941; margin-bottom: 8px; }
.manage-card-actions {
    display: flex; gap: 6px; flex-wrap: wrap;
}

/* ── Toggle switches ── */
.toggle-mini {
    display: flex; align-items: center; gap: 5px;
    font-size: .72rem; font-weight: 600; color: #666;
}
.switch-mini { position: relative; width: 34px; height: 18px; flex-shrink: 0; }
.switch-mini input { display: none; }
.switch-slider {
    position: absolute; inset: 0; background: #ddd;
    border-radius: 18px; cursor: pointer; transition: background .2s;
}
.switch-slider::before {
    content: ''; position: absolute; width: 12px; height: 12px;
    background: #fff; border-radius: 50%; top: 3px; left: 3px;
    transition: left .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2);
}
.switch-mini input:checked + .switch-slider { background: #679941; }
.switch-mini input:checked + .switch-slider::before { left: 19px; }

/* ── Badge ── */
.badge-featured-home {
    position: absolute; top: 8px; left: 8px;
    background: #679941; color: #fff;
    border-radius: 4px; padding: 2px 7px;
    font-size: 10px; font-weight: 700;
}

/* ── Empty panel state ── */
.panel-empty {
    text-align: center; padding: 2rem 1rem; color: #bbb;
}
.panel-empty i { font-size: 2.5rem; display: block; margin-bottom: .5rem; }

/* ── Toast notification ── */
.toast-notify {
    position: fixed; bottom: 24px; right: 24px; z-index: 9999;
    background: #333; color: #fff; border-radius: 8px;
    padding: 12px 20px; font-size: .85rem; font-weight: 500;
    box-shadow: 0 4px 20px rgba(0,0,0,.25);
    transform: translateY(80px); opacity: 0;
    transition: transform .3s, opacity .3s;
    pointer-events: none;
}
.toast-notify.show { transform: translateY(0); opacity: 1; }
.toast-notify.success { border-left: 4px solid #679941; }
.toast-notify.error   { border-left: 4px solid #e74c3c; }
</style>
@endsection

@section('content')

{{-- ══════════════════════════════════════════════════════
     PANEL EXCLUSIVO: ADMIN Y SELLER
     Sección para destacar/gestionar productos en el home
════════════════════════════════════════════════════════ --}}
@auth
    @if(in_array(Auth::user()->user_type, ['admin', 'seller']))
    <div class="container mt-3">
        <div class="seller-panel">

            <div class="seller-panel-header">
                <div>
                    <h4>
                        <i class="las la-store mr-2"></i>
                        @if(Auth::user()->user_type === 'admin')
                            Panel Admin — Gestión de Productos en Home
                        @else
                            Mis Productos — Destacar en Home
                        @endif
                    </h4>
                    <p style="color:rgba(255,255,255,.75); font-size:.78rem; margin:2px 0 0;">
                        Activa el toggle <strong>Home</strong> para que el producto aparezca en la sección destacada.
                    </p>
                </div>
                <div class="header-actions">
                    <a href="{{ route('seller.products.create') }}" class="btn-add-product">
                        <i class="las la-plus"></i> Agregar producto
                    </a>
                    <a href="{{ route('seller.products.index') }}" class="btn-add-product" style="background:rgba(255,255,255,.15); color:#fff;">
                        <i class="las la-list"></i> Ver todos
                    </a>
                </div>
            </div>

            <div class="seller-panel-body">
                @if($my_products && $my_products->count() > 0)
                    <div class="manage-grid">
                        @foreach($my_products as $product)
                        <div class="manage-card" id="mcard-{{ $product->id }}">

                            {{-- Badge si ya está en home --}}
                            @if($product->featured)
                                <span class="badge-featured-home" id="badge-{{ $product->id }}">
                                    <i class="las la-home"></i> En Home
                                </span>
                            @else
                                <span class="badge-featured-home" id="badge-{{ $product->id }}"
                                      style="display:none; background:#aaa;">
                                    <i class="las la-home"></i> En Home
                                </span>
                            @endif

                            {{-- Imagen --}}
                            @if($product->thumbnail)
                                <img src="{{ asset('storage/' . $product->thumbnail) }}"
                                     alt="{{ $product->name }}"
                                     class="manage-card-img">
                            @else
                                <div class="manage-card-placeholder">
                                    <i class="las la-image"></i>
                                </div>
                            @endif

                            <div class="manage-card-body">
                                <div class="manage-card-name" title="{{ $product->name }}">
                                    {{ $product->name }}
                                </div>
                                <div class="manage-card-price">
                                    ${{ number_format($product->unit_price, 2) }}
                                </div>

                                <div class="manage-card-actions">
                                    {{-- Toggle: Destacar en Home --}}
                                    <div class="toggle-mini">
                                        <label class="switch-mini">
                                            <input type="checkbox"
                                                   {{ $product->featured ? 'checked' : '' }}
                                                   onchange="toggleFeatured({{ $product->id }}, this)">
                                            <span class="switch-slider"></span>
                                        </label>
                                        Home
                                    </div>

                                    {{-- Toggle: Publicado --}}
                                    <div class="toggle-mini">
                                        <label class="switch-mini">
                                            <input type="checkbox"
                                                   {{ $product->published ? 'checked' : '' }}
                                                   onchange="togglePublished({{ $product->id }}, this)">
                                            <span class="switch-slider"></span>
                                        </label>
                                        Visible
                                    </div>
                                </div>

                                {{-- Editar --}}
                                <a href="{{ url('/seller/products/' . $product->id . '/edit') }}"
                                   style="display:block; margin-top:8px; font-size:.75rem; color:#679941; text-decoration:none; font-weight:600;">
                                    <i class="las la-edit mr-1"></i> Editar producto
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="panel-empty">
                        <i class="las la-box-open"></i>
                        <p style="font-size:.85rem; color:#999; margin:0;">
                            No tienes productos publicados aún.
                        </p>
                        <a href="{{ route('seller.products.create') }}"
                           style="color:#679941; font-size:.83rem; font-weight:600;">
                            + Agregar mi primer producto
                        </a>
                    </div>
                @endif
            </div>

        </div>
    </div>
    @endif
@endauth

{{-- HERO: Categorías + Slider --}}
<div class="home-banner-area mb-4 pt-3">
    <div class="container">
        <div class="row gutters-10 position-relative">

            {{-- PANEL DE CATEGORÍAS LATERAL --}}
            <div class="col-lg-3 position-static d-none d-lg-block">
                <div class="aiz-category-menu bg-white rounded shadow-sm">
                    <div class="p-3 bg-soft-primary d-none d-lg-block rounded-top all-category position-relative text-left">
                        <span class="fw-600 fs-16 mr-3">Categories</span>
                        <a href="{{ route('categories.index') }}" class="text-reset">
                            <span class="d-none d-lg-inline-block">See All ></span>
                        </a>
                    </div>
                    <ul class="list-unstyled categories no-scrollbar py-2 mb-0 text-left">
                        @foreach($categories as $category)
                        <li class="category-nav-element" data-id="{{ $category->id }}">
                            <a href="{{ route('category.show', $category->slug) }}" class="text-truncate text-reset py-2 px-3 d-block">
                                <img
                                    class="cat-image lazyload mr-2 opacity-60"
                                    src="{{ asset('assets/img/placeholder.jpg') }}"
                                    data-src="{{ uploaded_asset($category->icon) }}"
                                    width="16"
                                    alt="{{ $category->name }}"
                                    onerror="this.onerror=null;this.src='{{ asset('assets/img/placeholder.jpg') }}';"
                                >
                                <span class="cat-name">{{ $category->name }}</span>
                            </a>
                            <div class="sub-cat-menu"></div>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- SLIDER + CATEGORÍAS RÁPIDAS --}}
            <div class="col-lg-9">
                <div class="aiz-carousel dots-inside-bottom mobile-img-auto-height" data-arrows="true" data-dots="true" data-autoplay="true">
                    @foreach($banners as $banner)
                    <div class="carousel-box">
                        <a href="{{ $banner->link ?? '#' }}">
                            <img
                                class="d-block mw-100 img-fit rounded shadow-sm overflow-hidden"
                                src="{{ uploaded_asset($banner->image) }}"
                                alt="Woot promo"
                                height="315"
                                onerror="this.onerror=null;this.src='{{ asset('assets/img/placeholder.jpg') }}';"
                            >
                        </a>
                    </div>
                    @endforeach
                </div>

                <ul class="list-unstyled mb-0 row gutters-5">
                    @foreach($categories->take(6) as $category)
                    <li class="minw-0 col-4 col-md mt-3">
                        <a href="{{ route('category.show', $category->slug) }}" class="d-block rounded bg-white p-2 text-reset shadow-sm">
                            <img
                                src="{{ asset('assets/img/placeholder.jpg') }}"
                                data-src="{{ uploaded_asset($category->banner) }}"
                                alt="{{ $category->name }}"
                                class="lazyload img-fit"
                                height="78"
                                onerror="this.onerror=null;this.src='{{ asset('assets/img/placeholder-rect.jpg') }}';"
                            >
                            <div class="text-truncate fs-12 fw-600 mt-2 opacity-70">{{ $category->name }}</div>
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>

        </div>
    </div>
</div>

{{-- BANNERS PROMOCIONALES --}}
@if(isset($promo_banners_1) && count($promo_banners_1) > 0)
<div class="mb-4">
    <div class="container">
        <div class="row gutters-10">
            @foreach($promo_banners_1 as $promo)
            <div class="col-xl col-md-6">
                <div class="mb-3 mb-lg-0">
                    <a href="{{ $promo->link ?? '#' }}" class="d-block text-reset">
                        <img src="{{ asset('assets/img/placeholder-rect.jpg') }}"
                             data-src="{{ uploaded_asset($promo->image) }}"
                             alt="Woot promo"
                             class="img-fluid lazyload w-100">
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- PRODUCTOS NUEVOS --}}
<div id="section_newest">
    @if(isset($new_products) && count($new_products) > 0)
    <section class="mb-4">
        <div class="container">
            <div class="px-2 py-4 px-md-4 py-md-3 bg-white shadow-sm rounded">
                <div class="d-flex mb-3 align-items-baseline border-bottom">
                    <h3 class="h5 fw-700 mb-0">
                        <span class="border-bottom border-primary border-width-2 pb-3 d-inline-block">New Products</span>
                    </h3>
                </div>
                <div class="aiz-carousel gutters-10 half-outside-arrow"
                     data-items="6" data-xl-items="5" data-lg-items="4"
                     data-md-items="3" data-sm-items="2" data-xs-items="2" data-arrows="true">
                    @foreach($new_products as $product)
                    <div class="carousel-box">
                        @include('partials.product-card', ['product' => $product])
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif
</div>

{{-- ══════════════════════════════════════════════
     SECCIÓN FEATURED (visible para todos)
     Solo aparece si hay productos destacados
════════════════════════════════════════════════ --}}
@if(isset($featured_products) && $featured_products->count() > 0)
<section class="mb-4">
    <div class="container">
        <div class="px-2 py-4 px-md-4 py-md-3 bg-white shadow-sm rounded">
            <div class="d-flex mb-3 align-items-baseline border-bottom">
                <h3 class="h5 fw-700 mb-0">
                    <span class="border-bottom border-primary border-width-2 pb-3 d-inline-block">
                        <i class="las la-star text-warning mr-1"></i> Featured Products
                    </span>
                </h3>
                <a href="{{ route('products.index') }}" class="ml-auto mr-0 btn btn-primary btn-sm shadow-md">
                    View All
                </a>
            </div>
            <div class="aiz-carousel gutters-10 half-outside-arrow"
                 data-items="6" data-xl-items="5" data-lg-items="4"
                 data-md-items="3" data-sm-items="2" data-xs-items="2" data-arrows="true">
                @foreach($featured_products as $product)
                <div class="carousel-box">
                    @include('partials.product-card', ['product' => $product])
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

{{-- SECCIONES ADICIONALES (renderizadas en el servidor para que las
     imágenes aparezcan de inmediato, sin esperar peticiones AJAX) --}}
@include('partials.home-sections.best_selling', ['products' => $best_selling_products])
@include('partials.home-sections.home_categories', ['categories' => $home_categories])
@include('partials.home-sections.best_sellers', ['products' => $best_seller_products])

{{-- TOP 10 CATEGORÍAS Y MARCAS --}}
<section class="mb-4">
    <div class="container">
        <div class="row gutters-10">

            <div class="col-lg-6">
                <div class="d-flex mb-3 align-items-baseline border-bottom">
                    <h3 class="h5 fw-700 mb-0">
                        <span class="border-bottom border-primary border-width-2 pb-3 d-inline-block">Top 10 Categories</span>
                    </h3>
                    <a href="{{ route('categories.index') }}" class="ml-auto mr-0 btn btn-primary btn-sm shadow-md">View All Categories</a>
                </div>
                <div class="row gutters-5">
                    @foreach($top_categories as $category)
                    <div class="col-sm-6">
                        <a href="{{ route('category.show', $category->slug) }}" class="bg-white border d-block text-reset rounded p-2 hov-shadow-md mb-2">
                            <div class="row align-items-center no-gutters">
                                <div class="col-3 text-center">
                                    <img
                                        src="{{ asset('assets/img/placeholder.jpg') }}"
                                        data-src="{{ uploaded_asset($category->banner) }}"
                                        alt="{{ $category->name }}"
                                        class="img-fluid img lazyload h-60px"
                                        onerror="this.onerror=null;this.src='{{ asset('assets/img/placeholder.jpg') }}';"
                                    >
                                </div>
                                <div class="col-7">
                                    <div class="text-truncat-2 pl-3 fs-14 fw-600 text-left">{{ $category->name }}</div>
                                </div>
                                <div class="col-2 text-center">
                                    <i class="la la-angle-right text-primary"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="col-lg-6">
                <div class="d-flex mb-3 align-items-baseline border-bottom">
                    <h3 class="h5 fw-700 mb-0">
                        <span class="border-bottom border-primary border-width-2 pb-3 d-inline-block">Top 10 Brands</span>
                    </h3>
                    <a href="{{ route('brands.index') }}" class="ml-auto mr-0 btn btn-primary btn-sm shadow-md">View All Brands</a>
                </div>
                <div class="row gutters-5">
                    @foreach($top_brands as $brand)
                    <div class="col-sm-6">
                        <a href="{{ route('brands.show', $brand->slug) }}" class="bg-white border d-block text-reset rounded p-2 hov-shadow-md mb-2">
                            <div class="row align-items-center no-gutters">
                                <div class="col-4 text-center">
                                    <img
                                        src="{{ asset('assets/img/placeholder.jpg') }}"
                                        data-src="{{ uploaded_asset($brand->logo) }}"
                                        alt="{{ $brand->name }}"
                                        class="img-fluid img lazyload h-60px"
                                        onerror="this.onerror=null;this.src='{{ asset('assets/img/placeholder.jpg') }}';"
                                    >
                                </div>
                                <div class="col-6">
                                    <div class="text-truncate-2 pl-3 fs-14 fw-600 text-left">{{ $brand->name }}</div>
                                </div>
                                <div class="col-2 text-center">
                                    <i class="la la-angle-right text-primary"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</section>

{{-- Toast --}}
<div class="toast-notify" id="toast"></div>

@endsection

@section('extra_js')
<script>
$(document).ready(function () {
    // Subcategorías en hover
    $('.category-nav-element').each(function (i, el) {
        $(el).on('mouseover', function () {
            if (!$(el).find('.sub-cat-menu').hasClass('loaded')) {
                $.post('{{ route("categories.nav-element") }}', {
                    _token: AIZ.data.csrf,
                    id: $(el).data('id')
                }, function (data) {
                    $(el).find('.sub-cat-menu').addClass('loaded').html(data);
                });
            }
        });
    });

});

// ── Toggle Featured (destacar en home) ──────────────────────────
function toggleFeatured(productId, checkbox) {
    $.post('{{ url("/seller/products") }}/' + productId + '/toggle-featured', {
        _token: '{{ csrf_token() }}'
    }, function (data) {
        const badge = document.getElementById('badge-' + productId);
        if (data.featured) {
            badge.style.display = 'inline-block';
            badge.style.background = '#679941';
        } else {
            badge.style.display = 'none';
        }
        showToast(data.message, 'success');
    }).fail(function () {
        checkbox.checked = !checkbox.checked; // revertir si error
        showToast('Error al actualizar', 'error');
    });
}

// ── Toggle Published ─────────────────────────────────────────────
function togglePublished(productId, checkbox) {
    $.post('{{ url("/seller/products") }}/' + productId + '/toggle-published', {
        _token: '{{ csrf_token() }}'
    }, function (data) {
        showToast(data.message, 'success');
    }).fail(function () {
        checkbox.checked = !checkbox.checked;
        showToast('Error al actualizar', 'error');
    });
}

// ── Toast helper ─────────────────────────────────────────────────
function showToast(msg, type) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.className = 'toast-notify ' + (type || 'success') + ' show';
    setTimeout(() => toast.classList.remove('show'), 3000);
}
</script>
@endsection