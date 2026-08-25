@extends('layouts.app')

@section('title', 'All Categories')
@section('meta_description', 'Explora todas las categorías de la tienda')

@section('extra_css')
<style>
    body { background: #f5f6f8; }

    .cats-page { padding: 24px 0 40px; }

    /* ── Breadcrumb (mismo patrón que category-products) ── */
    .cat-breadcrumb {
        font-size: .82rem;
        color: #888;
        margin-bottom: 14px;
    }
    .cat-breadcrumb a { color: #679941; text-decoration: none; }
    .cat-breadcrumb a:hover { text-decoration: underline; }
    .cat-breadcrumb span { margin: 0 5px; color: #bbb; }

    .cats-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 18px;
    }
    .cats-title small {
        font-size: .8rem;
        font-weight: 400;
        color: #888;
        margin-left: 8px;
    }

    /* ── Tarjeta de categoría ── */
    .cat-card {
        background: #fff;
        border: 1px solid #e8eaed;
        border-radius: 8px;
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
        transition: box-shadow .15s, border-color .15s;
    }
    .cat-card:hover {
        box-shadow: 0 4px 14px rgba(0,0,0,.08);
        border-color: #d8dbe0;
    }

    .cat-card-head {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        border-bottom: 1px solid #f0f0f0;
        text-decoration: none;
    }
    .cat-card-head:hover { text-decoration: none; }
    .cat-card-head img {
        width: 52px;
        height: 52px;
        object-fit: contain;
        flex-shrink: 0;
    }
    .cat-card-name {
        font-size: .95rem;
        font-weight: 700;
        color: #333;
        line-height: 1.3;
    }
    .cat-card-head:hover .cat-card-name { color: #679941; }
    .cat-card-count {
        font-size: .76rem;
        color: #999;
        margin-top: 2px;
    }

    .cat-card-body { padding: 10px 16px 14px; flex: 1; }

    .subcat-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .subcat-list a {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        background: #f5f6f8;
        font-size: .78rem;
        color: #555;
        text-decoration: none;
        transition: background .15s, color .15s;
    }
    .subcat-list a:hover {
        background: rgba(103,153,65,.12);
        color: #679941;
        text-decoration: none;
    }

    .subcat-empty {
        font-size: .78rem;
        color: #aaa;
        font-style: italic;
    }

    .cats-empty {
        background: #fff;
        border: 1px solid #e8eaed;
        border-radius: 8px;
        padding: 48px 24px;
        text-align: center;
        color: #888;
    }
    .cats-empty i { font-size: 2.4rem; color: #ccc; display: block; margin-bottom: 10px; }
</style>
@endsection

@section('content')
<div class="cats-page">
    <div class="container">

        <div class="cat-breadcrumb">
            <a href="{{ url('/') }}">Home</a>
            <span>/</span>
            <strong style="color:#333;">All Categories</strong>
        </div>

        <h1 class="cats-title">
            All Categories
            <small>{{ $categories->count() }} {{ Str::plural('categoría', $categories->count()) }}</small>
        </h1>

        <div class="row gutters-10">
            @forelse($categories as $category)
                <div class="col-lg-4 col-sm-6 mb-3">
                    <div class="cat-card">
                        <a href="{{ route('category.show', $category->slug) }}" class="cat-card-head">
                            <img
                                src="{{ asset('assets/img/placeholder.jpg') }}"
                                data-src="{{ uploaded_asset($category->icon ?: $category->banner) }}"
                                alt="{{ $category->name }}"
                                class="lazyload"
                                onerror="this.onerror=null;this.src='{{ asset('assets/img/placeholder.jpg') }}';"
                            >
                            <div>
                                <div class="cat-card-name">{{ $category->name }}</div>
                                <div class="cat-card-count">{{ $category->product_count }} {{ Str::plural('producto', $category->product_count) }}</div>
                            </div>
                        </a>

                        <div class="cat-card-body">
                            @if($category->children->isNotEmpty())
                                <ul class="subcat-list">
                                    @foreach($category->children as $child)
                                        <li>
                                            <a href="{{ route('category.show', $child->slug) }}">{{ $child->name }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="subcat-empty">Sin subcategorías</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="cats-empty">
                        <i class="las la-folder-open"></i>
                        Todavía no hay categorías publicadas.
                    </div>
                </div>
            @endforelse
        </div>

    </div>
</div>
@endsection
