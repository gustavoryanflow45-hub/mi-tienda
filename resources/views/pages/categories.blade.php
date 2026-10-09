@extends('layouts.app')

@section('title', __('Categorías'))
@section('meta_description', __('Explora todas las categorías de la tienda'))

@section('extra_css')
<style>
    /*
     * Menú lateral con las categorías y, a la derecha, la cuadrícula de
     * círculos de la elegida. El storefront no carga Tailwind (solo el
     * Bootstrap 4 del theme), así que todo el layout va aquí.
     */
    .cats-page { background: #fff; padding: 0 0 24px; }

    .cats-title {
        font-size: 1.6rem;
        font-weight: 700;
        color: #222;
        margin: 0;
        padding: 18px 0 14px;
        border-bottom: 1px solid #ececec;
    }

    .cats-layout { display: flex; align-items: flex-start; }

    /* ── Menú lateral ── */
    .cats-nav {
        flex: 0 0 240px;
        width: 240px;
        background: #f4f4f4;
        position: sticky;
        top: 0;
        max-height: 100vh;
        overflow-y: auto;
        scrollbar-width: none;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .cats-nav::-webkit-scrollbar { display: none; }

    .cats-nav-link {
        display: block;
        position: relative;
        padding: 18px 16px 18px 20px;
        font-size: .95rem;
        line-height: 1.3;
        color: #333;
        text-decoration: none;
        word-break: break-word;
    }
    .cats-nav-link:hover { color: #000; text-decoration: none; background: #ececec; }
    .cats-nav-link.is-active {
        background: #fff;
        color: #111;
        font-weight: 700;
    }
    .cats-nav-link.is-active::before {
        content: '';
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 4px;
        background: #111;
    }

    /* ── Panel derecho ── */
    .cats-panels { flex: 1; min-width: 0; padding: 18px 0 0 24px; }
    .cats-panel[hidden] { display: none; }

    .cats-panel-head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
    }
    .cats-panel-name { font-size: 1.15rem; font-weight: 700; color: #222; margin: 0; }
    .cats-panel-count { font-size: .8rem; color: #999; white-space: nowrap; }

    .cats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 22px 14px;
    }

    .cats-tile {
        display: block;
        text-align: center;
        color: #333;
        text-decoration: none;
    }
    .cats-tile:hover { color: #000; text-decoration: none; }

    .cats-circle {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        max-width: 140px;
        aspect-ratio: 1 / 1;
        margin: 0 auto 8px;
        border-radius: 50%;
        background: #f2f2f2;
        overflow: hidden;
        transition: box-shadow .15s;
    }
    .cats-tile:hover .cats-circle { box-shadow: 0 0 0 2px #e2e2e2; }
    .cats-circle img {
        width: 78%;
        height: 78%;
        object-fit: contain;
        mix-blend-mode: multiply;   /* el fondo blanco de la foto se funde con el gris */
    }
    .cats-circle i { font-size: 2.6rem; color: #888; }

    .cats-tile-label {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        font-size: .9rem;
        line-height: 1.3;
    }

    .cats-empty {
        padding: 40px 12px;
        text-align: center;
        color: #999;
    }
    .cats-empty i { display: block; font-size: 2.4rem; color: #ccc; margin-bottom: 8px; }

    /* ── Teléfono: menú estrecho y tres columnas, como en la app ── */
    @media (max-width: 767.98px) {
        .cats-page > .container { padding-left: 0; padding-right: 0; }
        .cats-title { padding: 14px 16px 12px; font-size: 1.45rem; }

        .cats-nav { flex-basis: 34%; width: 34%; max-height: calc(100vh - 70px); }
        .cats-nav-link { padding: 16px 8px 16px 14px; font-size: .85rem; }

        .cats-panels { padding: 14px 10px 0; }
        .cats-panel-name { font-size: 1rem; }
        .cats-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px 8px; }
        .cats-tile-label { font-size: .78rem; }
        .cats-circle i { font-size: 2rem; }
    }
</style>
@endsection

@section('content')
@php
    // Pestañas en el orden del menú: Destacados primero, luego las raíces.
    $tabs = collect([[
        'slug' => $featuredTab,
        'name' => __('Destacados'),
        'children' => collect(),
        'products' => $featured,
        'count' => null,
        'url' => route('products.index'),
    ]])->concat($categories->map(fn ($category) => [
        'slug' => $category->slug,
        'name' => $category->name,
        'children' => $category->children,
        'products' => $category->panel_products,
        'count' => $category->product_count,
        'url' => route('category.show', $category->slug),
    ]));
@endphp

<div class="cats-page">
    <div class="container">

        <h1 class="cats-title">{{ __('Categorías') }}</h1>

        @if($categories->isEmpty() && $featured->isEmpty())
            <div class="cats-empty">
                <i class="las la-folder-open"></i>
                {{ __('Todavía no hay categorías publicadas.') }}
            </div>
        @else
            <div class="cats-layout">

                <ul class="cats-nav" role="tablist">
                    @foreach($tabs as $tab)
                        <li>
                            <a href="{{ route('categories.index', ['c' => $tab['slug']]) }}"
                               class="cats-nav-link {{ $tab['slug'] === $selected ? 'is-active' : '' }}"
                               role="tab"
                               aria-selected="{{ $tab['slug'] === $selected ? 'true' : 'false' }}"
                               data-cat-tab="{{ $tab['slug'] }}">{{ $tab['name'] }}</a>
                        </li>
                    @endforeach
                </ul>

                <div class="cats-panels">
                    @foreach($tabs as $tab)
                        <section class="cats-panel" data-cat-panel="{{ $tab['slug'] }}" role="tabpanel"
                                 @if($tab['slug'] !== $selected) hidden @endif>

                            <div class="cats-panel-head">
                                <h2 class="cats-panel-name">{{ $tab['name'] }}</h2>
                                @if(! is_null($tab['count']))
                                    <span class="cats-panel-count">{{ trans_choice(':count producto|:count productos', $tab['count']) }}</span>
                                @endif
                            </div>

                            @if($tab['children']->isEmpty() && $tab['products']->isEmpty())
                                <div class="cats-empty">
                                    <i class="las la-box-open"></i>
                                    {{ __('Aún no hay productos en esta categoría.') }}
                                </div>
                            @else
                                <div class="cats-grid">
                                    @foreach($tab['children'] as $child)
                                        <a href="{{ route('category.show', $child->slug) }}" class="cats-tile">
                                            <span class="cats-circle">
                                                <img src="{{ asset('assets/img/placeholder.jpg') }}"
                                                     data-src="{{ uploaded_asset($child->icon ?: $child->banner) }}"
                                                     alt="{{ $child->name }}" class="lazyload"
                                                     onerror="this.onerror=null;this.src='{{ asset('assets/img/placeholder.jpg') }}';">
                                            </span>
                                            <span class="cats-tile-label">{{ $child->name }}</span>
                                        </a>
                                    @endforeach

                                    @foreach($tab['products'] as $product)
                                        <a href="{{ route('products.show', $product->slug) }}" class="cats-tile">
                                            <span class="cats-circle">
                                                <img src="{{ asset('assets/img/placeholder.jpg') }}"
                                                     data-src="{{ uploaded_asset($product->thumbnail) }}"
                                                     alt="{{ $product->name }}" class="lazyload"
                                                     onerror="this.onerror=null;this.src='{{ asset('assets/img/placeholder.jpg') }}';">
                                            </span>
                                            <span class="cats-tile-label">{{ $product->name }}</span>
                                        </a>
                                    @endforeach

                                    <a href="{{ $tab['url'] }}" class="cats-tile">
                                        <span class="cats-circle"><i class="las la-th-large"></i></span>
                                        <span class="cats-tile-label">{{ __('Ver todos') }}</span>
                                    </a>
                                </div>
                            @endif
                        </section>
                    @endforeach
                </div>

            </div>
        @endif

    </div>
</div>
@endsection

@section('extra_js')
<script>
    // Cambio de pestaña sin recargar; el ?c= de la URL se actualiza para que
    // recargar o compartir el enlace abra la misma categoría.
    document.addEventListener('click', function (event) {
        var link = event.target.closest('[data-cat-tab]');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey) return;

        event.preventDefault();
        var slug = link.getAttribute('data-cat-tab');

        document.querySelectorAll('[data-cat-tab]').forEach(function (tab) {
            var active = tab === link;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        document.querySelectorAll('[data-cat-panel]').forEach(function (panel) {
            panel.hidden = panel.getAttribute('data-cat-panel') !== slug;
        });

        history.replaceState(null, '', link.href);

        // En el teléfono el panel puede haber quedado arriba, fuera de vista.
        var panels = document.querySelector('.cats-panels');
        if (panels && panels.getBoundingClientRect().top < 0) {
            panels.scrollIntoView({ block: 'start' });
        }
    });

    // Que la pestaña abierta por ?c= quede visible en el menú. Se mueve solo
    // el menú: scrollIntoView arrastraría también la página.
    (function () {
        var nav = document.querySelector('.cats-nav');
        var active = nav && nav.querySelector('.is-active');
        if (active) nav.scrollTop = active.parentNode.offsetTop - nav.clientHeight / 3;
    })();
</script>
@endsection
