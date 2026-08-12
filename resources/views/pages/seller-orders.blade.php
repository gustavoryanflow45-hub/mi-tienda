@extends('layouts.app')

@section('title', 'Pedidos de mi Tienda')

@section('extra_css')
<style>
    body { background-color: #f2f3f8; }

    /* ── Sidebar (igual que seller-products) ── */
    .dashboard-sidebar .profile-header {
        background: linear-gradient(135deg, #679941, #4e7a2e);
        border-radius: 0.75rem 0.75rem 0 0;
    }
    .verified-badge {
        background: linear-gradient(135deg, #43e97b, #38f9d7);
        color: #fff; border-radius: 20px; padding: 3px 12px;
        font-size: 11px; font-weight: 700; letter-spacing: 0.5px;
        display: inline-flex; align-items: center; gap: 4px;
    }
    .avatar-placeholder {
        width: 80px; height: 80px; border-radius: 50%;
        background: rgba(255,255,255,0.25); border: 3px solid rgba(255,255,255,0.5);
        display: flex; align-items: center; justify-content: center;
        font-size: 2rem; font-weight: 700; color: #fff; margin: 0 auto; overflow: hidden;
    }
    .avatar-placeholder img { width: 100%; height: 100%; object-fit: cover; }

    .aiz-side-nav-link {
        border-radius: 8px; transition: background 0.15s ease, color 0.15s ease;
        font-size: 14px; color: #555 !important;
    }
    .aiz-side-nav-link.active,
    .aiz-side-nav-link.bg-soft-primary {
        background-color: rgba(103,153,65,0.12) !important;
        color: #679941 !important; font-weight: 600;
    }
    .aiz-side-nav-link:hover { background-color: #f0f1f3 !important; color: #333 !important; opacity: 1 !important; }

    /* ── Panel de pedidos ── */
    .orders-panel { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); overflow: hidden; }
    .orders-panel-header {
        padding: 14px 20px; display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 10px; border-bottom: 1px solid #eee;
    }
    .orders-panel-header h5 { font-size: 0.95rem; font-weight: 700; color: #333; margin: 0; }
    .filter-bar { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .filter-bar input, .filter-bar select {
        border: 1px solid #ddd; border-radius: 4px; padding: 5px 12px;
        font-size: 0.83rem; height: 34px; outline: none;
    }
    .filter-bar input:focus, .filter-bar select:focus { border-color: #679941; }
    .btn-search {
        background: #4a5568; color: #fff; border: none; border-radius: 4px;
        padding: 5px 14px; font-size: 0.83rem; height: 34px; cursor: pointer; transition: background 0.2s;
    }
    .btn-search:hover { background: #2d3748; }

    .table-orders { width: 100%; border-collapse: collapse; font-size: 0.83rem; }
    .table-orders thead tr { background: #f8f9fa; border-bottom: 2px solid #eee; }
    .table-orders thead th { padding: 10px 14px; font-weight: 700; color: #555; white-space: nowrap; }
    .table-orders tbody tr { border-bottom: 1px solid #f0f0f0; transition: background 0.15s; }
    .table-orders tbody tr:hover { background: #f9fbf7; }
    .table-orders tbody td { padding: 10px 14px; color: #444; vertical-align: middle; }

    .nothing-found { text-align: center; padding: 3rem 1rem; color: #aaa; }
    .nothing-found i { font-size: 2.5rem; display: block; margin-bottom: 0.5rem; }
    .nothing-found span { font-size: 0.9rem; }

    .badge-status { border-radius:20px; padding:3px 10px; font-size:11px; font-weight:700; display:inline-block; }
    .badge-pending    { background:#fff3cd; color:#856404; }
    .badge-confirmed  { background:#cce5ff; color:#004085; }
    .badge-warehouse  { background:#e8d5f5; color:#5a1e82; }
    .badge-on_the_way { background:#d1ecf1; color:#0c5460; }
    .badge-delivered  { background:#d4edda; color:#155724; }
    .badge-cancelled  { background:#f8d7da; color:#721c24; }
    .badge-returned   { background:#e2e3e5; color:#383d41; }
    .badge-paid       { background:#d4edda; color:#155724; }
    .badge-unpaid     { background:#f8d7da; color:#721c24; }

    .btn-confirm {
        background: #679941; color: #fff; border: none; border-radius: 6px;
        padding: 6px 14px; font-size: 0.78rem; font-weight: 700; cursor: pointer;
        white-space: nowrap; transition: background .2s;
    }
    .btn-confirm:hover { background: #4e7a2e; }
    .btn-view {
        display: inline-flex; align-items: center; gap: 4px;
        color: #679941; font-size: 0.78rem; font-weight: 600; text-decoration: none;
    }
    .btn-view:hover { text-decoration: underline; }

    .alert-warehouse-success {
        background: #d4edda; border: 1px solid #c3e6cb; color: #155724;
        border-radius: 8px; padding: 12px 18px; margin-bottom: 18px;
        font-size: .875rem; font-weight: 600; display: flex; align-items: center; gap: 8px;
    }
    .alert-warehouse-error {
        background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24;
        border-radius: 8px; padding: 12px 18px; margin-bottom: 18px;
        font-size: .875rem; font-weight: 600; display: flex; align-items: center; gap: 8px;
    }
</style>
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row gutters-10">

            {{-- ══════════════════════════════════
                 SIDEBAR
            ══════════════════════════════════════ --}}
            <div class="col-lg-3 mb-4">
                <div class="dashboard-sidebar">
                    <div class="profile-header p-4 text-center text-white">
                        <div class="avatar-placeholder mb-3">
                            <img src="{{ asset('assets/img/avatar-place.png') }}"
                                 onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=679941&color=fff&size=80'"
                                 alt="{{ Auth::user()->name }}">
                        </div>
                        <h4 class="h5 fw-600 fs-18 mb-1">{{ strtoupper(Auth::user()->name) }}</h4>
                        <p class="mb-2 text-truncate opacity-80 fs-13">{{ Auth::user()->email }}</p>
                        <span class="verified-badge">
                            <i class="las la-check-circle"></i> Verified
                        </span>
                    </div>

                    <div class="bg-white shadow-sm rounded-bottom p-3">
                        <ul class="aiz-side-nav-list list-unstyled mb-0">
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/dashboard') }}" class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-home mr-2 fs-16"></i><span>Dashboard</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ route('seller.products.index') }}" class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-box mr-2 fs-16"></i><span>Productos</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ route('seller.orders.index') }}" class="aiz-side-nav-link bg-soft-primary active d-flex align-items-center text-reset p-2">
                                    <i class="las la-shopping-cart mr-2 fs-16"></i>
                                    <span>Pedidos</span>
                                    @php $newOrders = Auth::user()->newOrderNotificationsCount(); @endphp
                                    @if($newOrders > 0)
                                        <span style="margin-left:auto;background:#e74c3c;color:#fff;border-radius:12px;padding:1px 7px;font-size:11px;font-weight:700;">{{ $newOrders }}</span>
                                    @endif
                                </a>
                            </li>
                            @if(Auth::user()->isAdmin())
                                <li class="aiz-side-nav-item mb-1">
                                    <a href="{{ route('warehouse.index') }}" class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                        <i class="las la-warehouse mr-2 fs-16"></i><span>Almacén</span>
                                    </a>
                                </li>
                            @endif
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ route('wallet.index') }}" class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-wallet mr-2 fs-16"></i><span>Mi Billetera</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/profile') }}" class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-user-cog mr-2 fs-16"></i><span>Administrar Perfil</span>
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

                <h3 class="h4 fw-700 mb-4">Pedidos de mi Tienda</h3>

                @if(session('warehouse_success'))
                    <div class="alert-warehouse-success">
                        <i class="las la-check-circle" style="font-size:1.1rem;"></i>
                        {{ session('warehouse_success') }}
                    </div>
                @endif
                @if(session('warehouse_error'))
                    <div class="alert-warehouse-error">
                        <i class="las la-exclamation-circle" style="font-size:1.1rem;"></i>
                        {{ session('warehouse_error') }}
                    </div>
                @endif

                <div class="orders-panel">
                    <div class="orders-panel-header">
                        <h5>Pedidos pagados</h5>
                        <form method="GET" action="{{ route('seller.orders.index') }}" class="filter-bar">
                            <input type="text" name="code" value="{{ request('code') }}" placeholder="Código de pedido">
                            <select name="delivery_status" onchange="this.form.submit()">
                                <option value="">Todos los estados</option>
                                <option value="pending"    @selected(request('delivery_status') === 'pending')>Pendiente</option>
                                <option value="confirmed"  @selected(request('delivery_status') === 'confirmed')>Confirmado</option>
                                <option value="warehouse"  @selected(request('delivery_status') === 'warehouse')>En almacén</option>
                                <option value="on_the_way" @selected(request('delivery_status') === 'on_the_way')>En camino</option>
                                <option value="delivered"  @selected(request('delivery_status') === 'delivered')>Entregado</option>
                            </select>
                            <button type="submit" class="btn-search">Buscar</button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table-orders">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Código</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Total (tus productos)</th>
                                    <th>Pago</th>
                                    <th>Entrega</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $i => $order)
                                    @php
                                        $sellerSubtotal = $order->orderDetails->sum(fn($d) => $d->price * $d->quantity);
                                        $addr = is_array($order->shipping_address) ? $order->shipping_address : [];
                                    @endphp
                                    <tr>
                                        <td>{{ $orders->firstItem() + $i }}</td>
                                        <td style="font-weight:600;">{{ $order->code }}</td>
                                        <td>
                                            {{ $addr['full_name'] ?? $order->user->name ?? '—' }}
                                            @if(! empty($addr['phone']) || ! empty($addr['city']))
                                                <br><small style="color:#888;">
                                                    @if(! empty($addr['phone']))<i class="las la-phone"></i> {{ $addr['phone'] }}@endif
                                                    @if(! empty($addr['city'])) · {{ $addr['city'] }}@endif
                                                </small>
                                            @endif
                                        </td>
                                        <td>{{ $order->created_at->format('d-m-Y H:i') }}</td>
                                        <td style="font-weight:600;">${{ number_format($sellerSubtotal, 2) }}</td>
                                        <td>
                                            <span class="badge-status badge-{{ $order->payment_status === 'paid' ? 'paid' : 'unpaid' }}">
                                                {{ $order->payment_status === 'paid' ? 'Pagado' : 'Pendiente' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-status badge-{{ $order->delivery_status }}">
                                                {{ ucwords(str_replace('_', ' ', $order->delivery_status)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2" style="gap:10px;">
                                                @if($order->delivery_status === 'pending')
                                                    <form method="POST" action="{{ route('seller.orders.confirm', $order->id) }}"
                                                          onsubmit="return confirm('¿Confirmar el pedido #{{ $order->code }}?')">
                                                        @csrf
                                                        <button type="submit" class="btn-confirm">Confirmar</button>
                                                    </form>
                                                @endif
                                                <a href="{{ route('orders.show', $order->id) }}" class="btn-view">
                                                    Ver detalle <i class="las la-arrow-right"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="nothing-found">
                                                <i class="las la-frown-open"></i>
                                                <span>No tienes pedidos pagados todavía</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($orders->hasPages())
                        <div class="p-3">
                            {{ $orders->links() }}
                        </div>
                    @endif
                </div>

            </div>
            {{-- /col-lg-9 --}}

        </div>
    </div>
</section>
@endsection
