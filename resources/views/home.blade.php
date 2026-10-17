{{-- resources/views/home.blade.php --}}
@extends('layouts.app')

@section('title', 'Home')

@section('extra_css')
<style>
/* El menú lateral de categorías pasa de 10 a 35 entradas: sin tope se
   estiraba muy por debajo del slider. Se queda a su altura y hace scroll. */
@media (min-width: 992px) {
    .aiz-category-menu .categories { max-height: 440px; overflow-y: auto; }
}

/* El desplegable del tema ocupaba toda la columna derecha y tapaba el
   slider y las categorías rápidas, aunque la categoría no tuviera
   subcategorías. Ahora solo existe en las que tienen (.has-sub) y mide
   lo que su contenido. */
.aiz-category-menu .sub-cat-menu {
    width: 420px;
    height: auto;
    max-height: calc(100% + 20px);
    border-radius: 0 .25rem .25rem 0;
    box-shadow: 4px 4px 16px rgba(0, 0, 0, .12);
}
.aiz-category-menu .category-nav-element:not(.has-sub):hover > a {
    background: #f4f6f2;
    box-shadow: none;
}
</style>
@endsection

@section('content')

{{-- HERO: Categorías + Slider --}}
<div class="home-banner-area mb-4 pt-3">
    <div class="container">
        <div class="row gutters-10 position-relative">

            {{-- PANEL DE CATEGORÍAS LATERAL --}}
            <div class="col-lg-3 position-static d-none d-lg-block">
                <div class="aiz-category-menu bg-white rounded shadow-sm">
                    <div class="p-3 bg-soft-primary d-none d-lg-block rounded-top all-category position-relative text-left">
                        <span class="fw-600 fs-16 mr-3">{{ __('Categorías') }}</span>
                        <a href="{{ route('categories.index') }}" class="text-reset">
                            <span class="d-none d-lg-inline-block">{{ __('Ver todas') }} ></span>
                        </a>
                    </div>
                    <ul class="list-unstyled categories no-scrollbar py-2 mb-0 text-left">
                        @foreach($categories as $category)
                        <li class="category-nav-element {{ $category->active_children_count ? 'has-sub' : '' }}" data-id="{{ $category->id }}">
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
                            @if($category->active_children_count)
                                <div class="sub-cat-menu"></div>
                            @endif
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
                                alt="guStore"
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

{{-- PRODUCTOS NUEVOS --}}
<div id="section_newest">
    @if(isset($new_products) && count($new_products) > 0)
    <section class="mb-4">
        <div class="container">
            <div class="px-2 py-4 px-md-4 py-md-3 bg-white shadow-sm rounded">
                <div class="d-flex mb-3 align-items-baseline border-bottom">
                    <h3 class="h5 fw-700 mb-0">
                        <span class="border-bottom border-primary border-width-2 pb-3 d-inline-block">{{ __('Productos nuevos') }}</span>
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
                        <i class="las la-star text-warning mr-1"></i> {{ __('Productos destacados') }}
                    </span>
                </h3>
                <a href="{{ route('products.index') }}" class="ml-auto mr-0 btn btn-primary btn-sm shadow-md">
                    {{ __('Ver todo') }}
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
@foreach($featured_categories as [$featured_category, $featured_category_products])
    @include('partials.home-sections.category', ['category' => $featured_category, 'products' => $featured_category_products])
@endforeach

{{-- TOP 10 CATEGORÍAS Y MARCAS --}}
<section class="mb-4">
    <div class="container">
        <div class="row gutters-10">

            <div class="col-lg-6">
                <div class="d-flex mb-3 align-items-baseline border-bottom">
                    <h3 class="h5 fw-700 mb-0">
                        <span class="border-bottom border-primary border-width-2 pb-3 d-inline-block">{{ __('Top 10 categorías') }}</span>
                    </h3>
                    <a href="{{ route('categories.index') }}" class="ml-auto mr-0 btn btn-primary btn-sm shadow-md">{{ __('Ver todas las categorías') }}</a>
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
                        <span class="border-bottom border-primary border-width-2 pb-3 d-inline-block">{{ __('Top 10 marcas') }}</span>
                    </h3>
                    <a href="{{ route('brands.index') }}" class="ml-auto mr-0 btn btn-primary btn-sm shadow-md">{{ __('Ver todas las marcas') }}</a>
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

@endsection

@section('extra_js')
<script>
$(document).ready(function () {
    // Subcategorías en hover
    $('.category-nav-element.has-sub').each(function (i, el) {
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
</script>
@endsection