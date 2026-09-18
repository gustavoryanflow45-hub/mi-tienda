@extends('layouts.app')

@section('title', __('Mis productos'))

@section('extra_css')
<style>
    body { background-color: #f2f3f8; }

    /* ── Sidebar ── */
    .dashboard-sidebar .profile-header {
        background: linear-gradient(135deg, #679941, #4e7a2e);
        border-radius: 0.75rem 0.75rem 0 0;
    }
    .verified-badge {
        background: linear-gradient(135deg, #43e97b, #38f9d7);
        color: #fff;
        border-radius: 20px;
        padding: 3px 12px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .unverified-badge {
        background: linear-gradient(135deg, #f39c12, #f64f59);
        color: #fff;
        border-radius: 20px;
        padding: 3px 12px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .avatar-placeholder {
        width: 80px; height: 80px;
        border-radius: 50%;
        background: rgba(255,255,255,0.25);
        border: 3px solid rgba(255,255,255,0.5);
        display: flex; align-items: center; justify-content: center;
        font-size: 2rem; font-weight: 700; color: #fff;
        margin: 0 auto;
        overflow: hidden;
    }
    .avatar-placeholder img { width: 100%; height: 100%; object-fit: cover; }

    .aiz-side-nav-link {
        border-radius: 8px;
        transition: background 0.15s ease, color 0.15s ease;
        font-size: 14px;
        color: #555 !important;
    }
    .aiz-side-nav-link.active,
    .aiz-side-nav-link.bg-soft-primary {
        background-color: rgba(103,153,65,0.12) !important;
        color: #679941 !important;
        font-weight: 600;
    }
    .aiz-side-nav-link:hover {
        background-color: #f0f1f3 !important;
        color: #333 !important;
        opacity: 1 !important;
    }

    /* ── Action cards (My Shop Products / Product) ── */
    .action-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.07);
        text-align: center;
        padding: 2rem 1rem;
        cursor: pointer;
        transition: box-shadow 0.2s, transform 0.2s;
        text-decoration: none;
        color: #333;
        display: block;
        height: 100%;
    }
    .action-card:hover {
        box-shadow: 0 6px 24px rgba(103,153,65,0.18);
        transform: translateY(-3px);
        color: #333;
        text-decoration: none;
    }
    .action-card .icon-circle {
        width: 64px; height: 64px;
        border-radius: 50%;
        background: #4a5568;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 1rem;
    }
    .action-card .icon-circle i {
        font-size: 1.75rem;
        color: #fff;
    }
    .action-card h5 {
        font-size: 0.95rem;
        font-weight: 600;
        color: #333;
        margin: 0;
    }
    .action-card.locked {
        opacity: 0.55;
        cursor: not-allowed;
        pointer-events: none;
    }

    /* ── Products table panel ── */
    .products-panel {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.07);
        overflow: hidden;
    }
    .products-panel-header {
        padding: 14px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        border-bottom: 1px solid #eee;
    }
    .products-panel-header h5 {
        font-size: 0.95rem;
        font-weight: 700;
        color: #333;
        margin: 0;
    }
    .search-bar {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .search-bar input {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 5px 12px;
        font-size: 0.83rem;
        height: 34px;
        outline: none;
        width: 180px;
    }
    .search-bar input:focus { border-color: #679941; }
    .btn-search {
        background: #4a5568;
        color: #fff;
        border: none;
        border-radius: 4px;
        padding: 5px 14px;
        font-size: 0.83rem;
        height: 34px;
        cursor: pointer;
        transition: background 0.2s;
    }
    .btn-search:hover { background: #2d3748; }

    /* ── Table ── */
    .table-products {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.83rem;
    }
    .table-products thead tr {
        background: #f8f9fa;
        border-bottom: 2px solid #eee;
    }
    .table-products thead th {
        padding: 10px 14px;
        font-weight: 700;
        color: #555;
        white-space: nowrap;
    }
    .table-products tbody tr {
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.15s;
    }
    .table-products tbody tr:hover { background: #f9fbf7; }
    .table-products tbody td {
        padding: 10px 14px;
        color: #444;
        vertical-align: middle;
    }
    .nothing-found {
        text-align: center;
        padding: 3rem 1rem;
        color: #aaa;
    }
    .nothing-found i { font-size: 2.5rem; display: block; margin-bottom: 0.5rem; }
    .nothing-found span { font-size: 0.9rem; }

    /* ── Locked overlay (no verificado) ── */
    .locked-overlay {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.07);
        padding: 3rem 2rem;
        text-align: center;
    }
    .locked-overlay .lock-icon {
        width: 72px; height: 72px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f39c12, #f64f59);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 1.25rem;
    }
    .locked-overlay .lock-icon i { font-size: 2rem; color: #fff; }


    /* Badge status */
    .badge-published { background:#d4edda; color:#155724; border-radius:20px; padding:2px 10px; font-size:11px; font-weight:600; }
    .badge-unpublished { background:#f8d7da; color:#721c24; border-radius:20px; padding:2px 10px; font-size:11px; font-weight:600; }
    .badge-featured { background:#cce5ff; color:#004085; border-radius:20px; padding:2px 10px; font-size:11px; font-weight:600; }

    /* Toggle "Published" con hover */
    .publish-toggle {
        position: relative;
        display: inline-flex;
        align-items: center;
        cursor: pointer;
        border: none;
        background: none;
        padding: 0;
    }
    .publish-toggle .badge-published,
    .publish-toggle .badge-unpublished {
        transition: filter 0.15s, box-shadow 0.15s;
    }
    .publish-toggle:hover .badge-published,
    .publish-toggle:hover .badge-unpublished {
        filter: brightness(0.95);
        box-shadow: 0 0 0 2px rgba(103,153,65,0.35);
    }
    .publish-toggle .hover-hint {
        display: none;
        margin-left: 8px;
        font-size: 10px;
        color: #679941;
        font-weight: 600;
        white-space: nowrap;
    }
    .publish-toggle:hover .hover-hint { display: inline; }
    .publish-toggle.is-loading { opacity: 0.6; pointer-events: none; }
    .publish-toggle .hover-hint i { margin-right: 2px; }
    .btn-edit-product {
        display: inline-flex; align-items: center; gap: 4px;
        background: #f0f8ea; border: 1px solid #c5e0b4; color: #679941;
        border-radius: 6px; padding: 5px 12px; font-size: 12px; font-weight: 600;
        text-decoration: none; transition: background .15s, color .15s;
        white-space: nowrap;
    }
    .btn-edit-product:hover { background: #679941; color: #fff; }
</style>
@endsection

@section('content')

<section class="py-5">
    <div class="container">
        <div class="row gutters-10">

            {{-- ══════════════════════════════════
                 SIDEBAR — igual que verified_blade
            ══════════════════════════════════════ --}}
            <div class="col-lg-3 mb-4">
                <div class="dashboard-sidebar">

                    {{-- Perfil --}}
                    <div class="profile-header p-4 text-center text-white">
                        <div class="avatar-placeholder mb-3">
                            <img src="{{ asset('assets/img/avatar-place.png') }}"
                                 onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=679941&color=fff&size=80'"
                                 alt="{{ Auth::user()->name }}">
                        </div>
                        <h4 class="h5 fw-600 fs-18 mb-1">{{ strtoupper(Auth::user()->name) }}</h4>
                        <p class="mb-2 text-truncate opacity-80 fs-13">{{ Auth::user()->email }}</p>

                        @if(Auth::user()->email_verified_at || Auth::user()->email_verified)
                            <span class="verified-badge">
                                <i class="las la-check-circle"></i> Verified
                            </span>
                        @else
                            <span class="unverified-badge">
                                <i class="las la-times-circle"></i> Not Verified
                            </span>
                        @endif
                    </div>

                    {{-- Menú --}}
                    <div class="bg-white shadow-sm rounded-bottom p-3">
                        <ul class="aiz-side-nav-list list-unstyled mb-0">
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/dashboard') }}"
                                   class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-home mr-2 fs-16"></i>
                                    <span>{{ __('dashboard.nav.dashboard') }}</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/orders') }}"
                                   class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-file-invoice mr-2 fs-16"></i>
                                    <span>{{ __('dashboard.nav.purchase_history') }}</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/wishlist') }}"
                                   class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-heart mr-2 fs-16"></i>
                                    <span>{{ __('dashboard.nav.wishlist') }}</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/seller/products') }}"
                                   class="aiz-side-nav-link bg-soft-primary active d-flex align-items-center text-reset p-2">
                                    <i class="las la-box mr-2 fs-16"></i>
                                    <span>{{ __('dashboard.nav.products') }}</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ route('seller.orders.index') }}"
                                   class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-shopping-cart mr-2 fs-16"></i>
                                    <span>{{ __('dashboard.nav.orders') }}</span>
                                    @php $newOrders = Auth::user()->newOrderNotificationsCount(); @endphp
                                    @if($newOrders > 0)
                                        <span style="margin-left:auto;background:#e74c3c;color:#fff;border-radius:12px;padding:1px 7px;font-size:11px;font-weight:700;">{{ $newOrders }}</span>
                                    @endif
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ route('wallet.index') }}"
                                   class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-wallet mr-2 fs-16"></i>
                                    <span>{{ __('dashboard.nav.wallet') }}</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/profile') }}"
                                   class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-user-cog mr-2 fs-16"></i>
                                    <span>{{ __('dashboard.nav.manage_profile') }}</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                </div>
            </div>
            {{-- /sidebar --}}

            {{-- ══════════════════════════════════
                 CONTENIDO PRINCIPAL
            ══════════════════════════════════════ --}}
            <div class="col-lg-9">

                <h3 class="h4 fw-700 mb-4">{{ __('Productos') }}</h3>

                @php
                    $isVerified = Auth::user()->email_verified_at || Auth::user()->email_verified;
                @endphp

                {{-- ── Dos botones de acción ── --}}
                <div class="row gutters-10 mb-4">

                    {{-- My Shop Products --}}
                    <div class="col-6 col-md-6 mb-3">
                        @if($isVerified)
                            <a href="{{ url('/seller/products') }}" class="action-card">
                                <div class="icon-circle">
                                    <i class="las la-list-alt"></i>
                                </div>
                                <h5>{{ __('Mis productos') }}</h5>
                            </a>
                        @else
                            <div class="action-card locked" title="{{ __('Verifica tu correo para usar esta función') }}">
                                <div class="icon-circle" style="background:#ccc;">
                                    <i class="las la-lock"></i>
                                </div>
                                <h5>{{ __('Mis productos') }}</h5>
                                <small class="text-danger d-block mt-1" style="font-size:11px;">
                                    <i class="las la-exclamation-circle"></i> {{ __('Requiere verificación') }}
                                </small>
                            </div>
                        @endif
                    </div>

                    {{-- Add Product (unit or batch) --}}
                    <div class="col-6 col-md-6 mb-3">
                        @if($isVerified)
                            <div class="action-card" data-toggle="modal" data-target="#addProductModal" style="cursor:pointer;">
                                <div class="icon-circle">
                                    <i class="las la-list-alt"></i>
                                </div>
                                <h5>{{ __('Agregar producto') }}</h5>
                            </div>
                        @else
                            <div class="action-card locked" title="{{ __('Verifica tu correo para usar esta función') }}">
                                <div class="icon-circle" style="background:#ccc;">
                                    <i class="las la-lock"></i>
                                </div>
                                <h5>{{ __('Agregar producto') }}</h5>
                                <small class="text-danger d-block mt-1" style="font-size:11px;">
                                    <i class="las la-exclamation-circle"></i> {{ __('Requiere verificación') }}
                                </small>
                            </div>
                        @endif
                    </div>

                </div>
                {{-- /action cards --}}

                {{-- ── Alerta si no está verificado ── --}}
                @if(!$isVerified)
                    <div class="locked-overlay mb-4">
                        <div class="lock-icon">
                            <i class="las la-lock"></i>
                        </div>
                        <h5 class="fw-700 mb-2">{{ __('Funciones bloqueadas') }}</h5>
                        <p class="opacity-60 fs-14 mb-3">
                            {{ __('Debes verificar tu correo electrónico para poder agregar y gestionar productos en tu tienda.') }}
                        </p>
                        <a href="{{ route('verification.notice') }}" class="btn btn-warning fw-600 px-4">
                            <i class="las la-envelope mr-1"></i> {{ __('Verificar correo') }}
                        </a>
                    </div>
                @endif

                {{-- ── Tabla de productos (solo si verificado) ── --}}
                @if($isVerified)
                    <div class="products-panel">
                        <div class="products-panel-header">
                            <h5>{{ __('Mis productos') }}</h5>
                            <form method="GET" action="{{ url('/seller/products') }}" class="search-bar">
                                <input type="text"
                                       name="search"
                                       value="{{ request('search') }}"
                                       placeholder="{{ __('Buscar productos...') }}">
                                <button type="submit" class="btn-search">{{ __('Buscar') }}</button>
                            </form>
                        </div>

                        <div class="table-responsive">
                            <table class="table-products">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('Nombre') }}</th>
                                        <th>{{ __('Categoría') }}</th>
                                        <th>{{ __('Stock') }}</th>
                                        <th>SKU</th>
                                        <th>{{ __('Precio') }}</th>
                                        <th>{{ __('Publicado') }}</th>
                                        <th>{{ __('Destacado') }}</th>
                                        <th class="text-right">{{ __('Acciones') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($products ?? [] as $i => $product)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    @if($product->thumbnail)
                                                        <img src="{{ asset('storage/' . $product->thumbnail) }}"
                                                             alt="{{ $product->name }}"
                                                             style="width:36px;height:36px;object-fit:cover;border-radius:4px;">
                                                    @endif
                                                    <span>{{ $product->name }}</span>
                                                </div>
                                            </td>
                                            <td>{{ $product->category->name ?? '—' }}</td>
                                            <td>
                                                {{ $product->stocks->sum('qty') ?? 0 }}
                                            </td>
                                            <td>{{ $product->stocks->first()->sku ?? '—' }}</td>
                                            <td>${{ number_format($product->unit_price, 2) }}</td>
                                            <td>
                                                <button type="button"
                                                        class="publish-toggle"
                                                        data-product-id="{{ $product->id }}"
                                                        data-toggle-published
                                                        title="{{ $product->published ? __('Clic para despublicar') : __('Clic para publicar y mostrar de nuevo en el home') }}">
                                                    <span class="publish-badge">
                                                        @if($product->published)
                                                            <span class="badge-published">{{ __('Sí') }}</span>
                                                        @else
                                                            <span class="badge-unpublished">{{ __('No') }}</span>
                                                        @endif
                                                    </span>
                                                    <span class="hover-hint">
                                                        <i class="las la-sync-alt"></i>{{ $product->published ? __('Despublicar') : __('Publicar') }}
                                                    </span>
                                                </button>
                                            </td>
                                            <td>
                                                @if($product->featured)
                                                    <span class="badge-featured">{{ __('Sí') }}</span>
                                                @else
                                                    <span style="color:#aaa;">{{ __('No') }}</span>
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                <a href="{{ route('seller.products.edit', $product->id) }}"
                                                   class="btn-edit-product"
                                                   title="{{ __('Editar producto') }}">
                                                    <i class="las la-edit"></i> {{ __('Editar') }}
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9">
                                                <div class="nothing-found">
                                                    <i class="las la-frown-open"></i>
                                                    <span>{{ __('No se encontró nada') }}</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Paginación --}}
                        @if(isset($products) && $products->hasPages())
                            <div class="p-3">
                                {{ $products->links() }}
                            </div>
                        @endif
                    </div>
                @endif

            </div>
            {{-- /col-lg-9 --}}

        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════
     MODAL — Elegir tipo de producto (unit / batch)
     Solo aparece si está verificado
══════════════════════════════════════════════════ --}}
@if($isVerified ?? false)
<div class="modal fade" id="addProductModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius:14px; overflow:hidden;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-700">{{ __('Agregar producto') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body pt-2 pb-4">
                <p class="opacity-60 fs-14 mb-4">{{ __('¿Cómo deseas agregar el producto?') }}</p>
                <div class="row gutters-10">

                    {{-- Por unidad --}}
                    <div class="col-6">
                        <a href="{{ url('/seller/products/create') }}"
                           class="action-card d-block text-center p-4"
                           style="border: 2px solid #e0e0e0; border-radius:12px; text-decoration:none; color:#333; transition: border-color 0.2s, box-shadow 0.2s;"
                           onmouseover="this.style.borderColor='#679941';this.style.boxShadow='0 4px 16px rgba(103,153,65,0.2)'"
                           onmouseout="this.style.borderColor='#e0e0e0';this.style.boxShadow='none'">
                            <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#679941,#4e7a2e);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                                <i class="las la-box" style="font-size:1.6rem;color:#fff;"></i>
                            </div>
                            <h6 class="fw-700 mb-1">{{ __('Por unidad') }}</h6>
                            <small class="opacity-60">{{ __('Agrega un producto individual con sus variantes y stock') }}</small>
                        </a>
                    </div>

                    {{-- Por lote --}}
                    <div class="col-6">
                        <a href="{{ url('/seller/products/bulk') }}"
                           class="action-card d-block text-center p-4"
                           style="border: 2px solid #e0e0e0; border-radius:12px; text-decoration:none; color:#333; transition: border-color 0.2s, box-shadow 0.2s;"
                           onmouseover="this.style.borderColor='#679941';this.style.boxShadow='0 4px 16px rgba(103,153,65,0.2)'"
                           onmouseout="this.style.borderColor='#e0e0e0';this.style.boxShadow='none'">
                            <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#4776e6,#8e54e9);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                                <i class="las la-boxes" style="font-size:1.6rem;color:#fff;"></i>
                            </div>
                            <h6 class="fw-700 mb-1">{{ __('Por lote') }}</h6>
                            <small class="opacity-60">{{ __('Sube múltiples productos a la vez mediante un archivo CSV') }}</small>
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@section('extra_js')
<script>
    // ── Toggle "Published" desde la tabla de My Shop Products ──
    document.querySelectorAll('[data-toggle-published]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (btn.classList.contains('is-loading')) return;
            btn.classList.add('is-loading');

            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const id   = btn.dataset.productId;

            fetch(`/seller/products/${id}/toggle-published`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
            })
                .then(res => {
                    if (!res.ok) throw new Error(@json(__('No se pudo actualizar el estado.')));
                    return res.json();
                })
                .then(data => {
                    const badgeSpan = btn.querySelector('.publish-badge');
                    const hint = btn.querySelector('.hover-hint');

                    if (data.published) {
                        badgeSpan.innerHTML = '<span class="badge-published">' + @json(__('Sí')) + '</span>';
                        hint.innerHTML = '<i class="las la-sync-alt"></i>Despublicar';
                        btn.title = @json(__('Clic para despublicar'));
                    } else {
                        badgeSpan.innerHTML = '<span class="badge-unpublished">' + @json(__('No')) + '</span>';
                        hint.innerHTML = '<i class="las la-sync-alt"></i>Publicar';
                        btn.title = @json(__('Clic para publicar y mostrar de nuevo en el home'));
                    }
                })
                .catch(() => {
                    alert(@json(__('No se pudo actualizar el estado de publicación. Intenta de nuevo.')));
                })
                .finally(() => {
                    btn.classList.remove('is-loading');
                });
        });
    });
</script>
@endsection