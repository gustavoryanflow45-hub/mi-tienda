@extends('layouts.app')

@section('title', 'My Shop Products')

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

    /* ── Logout modal ── */
    .logout-modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.55); z-index: 9999;
        align-items: center; justify-content: center;
    }
    .logout-modal-overlay.show { display: flex; }
    .logout-modal-box {
        background: #fff; border-radius: 16px; padding: 2rem 1.75rem;
        width: 90%; max-width: 360px; text-align: center;
        box-shadow: 0 8px 32px rgba(0,0,0,0.18);
        animation: modalIn 0.25s ease;
    }
    @keyframes modalIn {
        from { transform: translateY(30px); opacity: 0; }
        to   { transform: translateY(0);    opacity: 1; }
    }
    .logout-icon {
        width: 64px; height: 64px;
        background: linear-gradient(135deg, #f64f59, #c471ed);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 1rem;
    }
    .logout-icon i { font-size: 2rem; color: #fff; }
    .btn-logout-confirm {
        display: block; width: 100%; padding: 0.65rem;
        background: linear-gradient(135deg, #679941, #4e7a2e);
        color: #fff; border: none; border-radius: 8px;
        font-weight: 600; font-size: 1rem; cursor: pointer;
        margin-bottom: 0.75rem; transition: opacity 0.2s;
    }
    .btn-logout-confirm:hover { opacity: 0.9; }
    .btn-logout-cancel {
        display: block; width: 100%; padding: 0.65rem;
        background: #f1f3f5; color: #444; border: none;
        border-radius: 8px; font-weight: 600; font-size: 1rem; cursor: pointer;
    }
    .btn-logout-cancel:hover { background: #e2e6ea; }

    .aiz-mobile-bottom-nav {
        border-radius: 16px 16px 0 0;
        padding-bottom: env(safe-area-inset-bottom, 0);
    }

    /* Badge status */
    .badge-published { background:#d4edda; color:#155724; border-radius:20px; padding:2px 10px; font-size:11px; font-weight:600; }
    .badge-unpublished { background:#f8d7da; color:#721c24; border-radius:20px; padding:2px 10px; font-size:11px; font-weight:600; }
    .badge-featured { background:#cce5ff; color:#004085; border-radius:20px; padding:2px 10px; font-size:11px; font-weight:600; }
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
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/orders') }}"
                                   class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-file-invoice mr-2 fs-16"></i>
                                    <span>Purchase History</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="#"
                                   class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-reply mr-2 fs-16"></i>
                                    <span>Sent Refund Request</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/wishlist') }}"
                                   class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-heart mr-2 fs-16"></i>
                                    <span>Wishlist</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="#"
                                   class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-sliders-h mr-2 fs-16"></i>
                                    <span>Comparar</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/seller/products') }}"
                                   class="aiz-side-nav-link bg-soft-primary active d-flex align-items-center text-reset p-2">
                                    <i class="las la-box mr-2 fs-16"></i>
                                    <span>Products</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="#"
                                   class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-boxes mr-2 fs-16"></i>
                                    <span>Wholesale Products</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ route('seller.orders.index') }}"
                                   class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-shopping-cart mr-2 fs-16"></i>
                                    <span>Pedidos</span>
                                    @php $unread = Auth::user()->unreadNotifications->count(); @endphp
                                    @if($unread > 0)
                                        <span style="margin-left:auto;background:#e74c3c;color:#fff;border-radius:12px;padding:1px 7px;font-size:11px;font-weight:700;">{{ $unread }}</span>
                                    @endif
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ route('wallet.index') }}"
                                   class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-wallet mr-2 fs-16"></i>
                                    <span>Mi Billetera</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/profile') }}"
                                   class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-user-cog mr-2 fs-16"></i>
                                    <span>Administrar Perfil</span>
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

                <h3 class="h4 fw-700 mb-4">Products</h3>

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
                                <h5>My Shop Products</h5>
                            </a>
                        @else
                            <div class="action-card locked" title="Verify your email to access this feature">
                                <div class="icon-circle" style="background:#ccc;">
                                    <i class="las la-lock"></i>
                                </div>
                                <h5>My Shop Products</h5>
                                <small class="text-danger d-block mt-1" style="font-size:11px;">
                                    <i class="las la-exclamation-circle"></i> Requiere verificación
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
                                <h5>Product</h5>
                            </div>
                        @else
                            <div class="action-card locked" title="Verify your email to access this feature">
                                <div class="icon-circle" style="background:#ccc;">
                                    <i class="las la-lock"></i>
                                </div>
                                <h5>Product</h5>
                                <small class="text-danger d-block mt-1" style="font-size:11px;">
                                    <i class="las la-exclamation-circle"></i> Requiere verificación
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
                        <h5 class="fw-700 mb-2">Funciones bloqueadas</h5>
                        <p class="opacity-60 fs-14 mb-3">
                            Debes verificar tu correo electrónico para poder agregar y gestionar productos en tu tienda.
                        </p>
                        <a href="{{ url('/email/verify') }}" class="btn btn-warning fw-600 px-4">
                            <i class="las la-envelope mr-1"></i> Verificar Email
                        </a>
                    </div>
                @endif

                {{-- ── Tabla de productos (solo si verificado) ── --}}
                @if($isVerified)
                    <div class="products-panel">
                        <div class="products-panel-header">
                            <h5>My Shop Products</h5>
                            <form method="GET" action="{{ url('/seller/products') }}" class="search-bar">
                                <input type="text"
                                       name="search"
                                       value="{{ request('search') }}"
                                       placeholder="Search products">
                                <button type="submit" class="btn-search">Submit</button>
                            </form>
                        </div>

                        <div class="table-responsive">
                            <table class="table-products">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Current Qty</th>
                                        <th>SKU</th>
                                        <th>Price</th>
                                        <th>Published</th>
                                        <th>Featured</th>
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
                                                @if($product->published)
                                                    <span class="badge-published">Yes</span>
                                                @else
                                                    <span class="badge-unpublished">No</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($product->featured)
                                                    <span class="badge-featured">Yes</span>
                                                @else
                                                    <span style="color:#aaa;">No</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8">
                                                <div class="nothing-found">
                                                    <i class="las la-frown-open"></i>
                                                    <span>Nothing found</span>
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
                <h5 class="modal-title fw-700">Agregar Producto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body pt-2 pb-4">
                <p class="opacity-60 fs-14 mb-4">¿Cómo deseas agregar el producto?</p>
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
                            <h6 class="fw-700 mb-1">Por Unidad</h6>
                            <small class="opacity-60">Agrega un producto individual con sus variantes y stock</small>
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
                            <h6 class="fw-700 mb-1">Por Lote</h6>
                            <small class="opacity-60">Sube múltiples productos a la vez mediante un archivo CSV</small>
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ── Mobile bottom nav ── --}}
<div class="aiz-mobile-bottom-nav d-xl-none fixed-bottom bg-white shadow-lg border-top">
    <div class="row align-items-center gutters-5">
        <div class="col">
            <a href="{{ url('/') }}" class="text-reset d-block text-center pb-2 pt-3">
                <i class="las la-home fs-20 opacity-60"></i>
                <span class="d-block fs-10 fw-600 opacity-60">Home</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ url('/categories') }}" class="text-reset d-block text-center pb-2 pt-3">
                <i class="las la-list-ul fs-20 opacity-60"></i>
                <span class="d-block fs-10 fw-600 opacity-60">Categories</span>
            </a>
        </div>
        <div class="col-auto">
            <a href="{{ url('/cart') }}" class="text-reset d-block text-center pb-2 pt-3">
                <span class="align-items-center bg-primary border border-white border-width-4 d-flex justify-content-center position-relative rounded-circle size-50px"
                      style="margin-top:-33px;">
                    <i class="las la-shopping-bag la-2x text-white"></i>
                </span>
                <span class="d-block mt-1 fs-10 fw-600 opacity-60">Cart (0)</span>
            </a>
        </div>
        <div class="col">
            <a href="#" class="text-reset d-block text-center pb-2 pt-3">
                <i class="las la-bell fs-20 opacity-60"></i>
                <span class="d-block fs-10 fw-600 opacity-60">Alerts</span>
            </a>
        </div>
        <div class="col">
            <button onclick="document.getElementById('logoutModal').classList.add('show')"
                    class="btn p-0 d-block w-100 text-center pb-2 pt-3"
                    style="background:none;border:none;">
                <i class="las la-user-circle fs-20 opacity-60"></i>
                <span class="d-block fs-10 fw-600 opacity-60">Account</span>
            </button>
        </div>
    </div>
</div>

{{-- ── Modal Logout ── --}}
<div class="logout-modal-overlay" id="logoutModal">
    <div class="logout-modal-box">
        <div class="logout-icon">
            <i class="las la-sign-out-alt"></i>
        </div>
        <h5 class="fw-700 mb-1">Sign out?</h5>
        <p class="opacity-60 fs-14 mb-4">Are you sure you want to log out of your account?</p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout-confirm">
                <i class="las la-sign-out-alt mr-1"></i> Yes, sign out
            </button>
        </form>
        <button class="btn-logout-cancel"
                onclick="document.getElementById('logoutModal').classList.remove('show')">
            Cancel
        </button>
    </div>
</div>

@endsection

@section('extra_js')
<script>
    document.getElementById('logoutModal').addEventListener('click', function (e) {
        if (e.target === this) this.classList.remove('show');
    });
</script>
@endsection