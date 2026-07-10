@extends('layouts.app')

@section('title', 'Panel de Almacén')

@section('extra_css')
<style>
    body { background-color: #f2f3f8; }

    /* ── Header ── */
    .warehouse-header {
        background: linear-gradient(135deg, #5a1e82, #7b3cb8);
        border-radius: 12px;
        padding: 22px 28px;
        color: #fff;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }
    .warehouse-header h2 { font-size: 1.25rem; font-weight: 700; margin: 0 0 4px; }
    .warehouse-header p  { font-size: .85rem; opacity: .85; margin: 0; }
    .warehouse-header .header-icon { font-size: 2.6rem; opacity: .5; }

    /* ── Stats cards ── */
    .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
    @media (max-width: 768px) { .stats-row { grid-template-columns: 1fr; } }
    .stat-card {
        background: #fff; border-radius: 10px; padding: 18px 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,.07);
        display: flex; align-items: center; gap: 14px;
    }
    .stat-card .stat-icon {
        width: 48px; height: 48px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
    }
    .stat-icon.warehouse  { background: #e8d5f5; color: #5a1e82; }
    .stat-icon.on_the_way { background: #d1ecf1; color: #0c5460; }
    .stat-icon.delivered  { background: #d4edda; color: #155724; }
    .stat-card .stat-value { font-size: 1.5rem; font-weight: 800; color: #222; line-height: 1.1; }
    .stat-card .stat-label { font-size: .78rem; color: #888; font-weight: 600; }

    /* ── Llegadas nuevas ── */
    .arrivals-box {
        background: #fdf6e3; border: 1px solid #f0d998; border-radius: 10px;
        padding: 14px 18px; margin-bottom: 20px;
    }
    .arrivals-box h6 {
        font-size: .85rem; font-weight: 700; color: #7a5c00; margin: 0 0 8px;
        display: flex; align-items: center; gap: 6px;
    }
    .arrivals-box ul { margin: 0; padding-left: 0; list-style: none; }
    .arrivals-box li {
        font-size: .82rem; color: #6b5310; padding: 4px 0;
        border-bottom: 1px dashed #eadfa8; display: flex; align-items: center; gap: 6px;
    }
    .arrivals-box li:last-child { border-bottom: none; }

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
    .filter-bar input:focus, .filter-bar select:focus { border-color: #5a1e82; }
    .btn-search {
        background: #4a5568; color: #fff; border: none; border-radius: 4px;
        padding: 5px 14px; font-size: 0.83rem; height: 34px; cursor: pointer; transition: background 0.2s;
    }
    .btn-search:hover { background: #2d3748; }

    .table-orders { width: 100%; border-collapse: collapse; font-size: 0.83rem; }
    .table-orders thead tr { background: #f8f9fa; border-bottom: 2px solid #eee; }
    .table-orders thead th { padding: 10px 14px; font-weight: 700; color: #555; white-space: nowrap; }
    .table-orders tbody tr { border-bottom: 1px solid #f0f0f0; transition: background 0.15s; }
    .table-orders tbody tr:hover { background: #faf7fd; }
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

    .btn-dispatch {
        display: inline-flex; align-items: center; gap: 6px;
        background: #5a1e82; color: #fff; border: none; border-radius: 6px;
        padding: 6px 14px; font-size: 0.78rem; font-weight: 700; cursor: pointer;
        white-space: nowrap; transition: background .2s;
    }
    .btn-dispatch:hover { background: #43135f; }
    .btn-deliver {
        display: inline-flex; align-items: center; gap: 6px;
        background: #679941; color: #fff; border: none; border-radius: 6px;
        padding: 6px 14px; font-size: 0.78rem; font-weight: 700; cursor: pointer;
        white-space: nowrap; transition: background .2s;
    }
    .btn-deliver:hover { background: #4e7a2e; }
    .btn-view {
        display: inline-flex; align-items: center; gap: 4px;
        color: #5a1e82; font-size: 0.78rem; font-weight: 600; text-decoration: none;
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

    .address-cell { max-width: 220px; font-size: .78rem; color: #666; }
</style>
@endsection

@section('content')
<section class="py-5">
    <div class="container">

        {{-- ── Header ── --}}
        <div class="warehouse-header">
            <div>
                <h2><i class="las la-warehouse mr-2"></i>Panel de Almacén</h2>
                <p>Recibe los pedidos que llegan al almacén, despáchalos y confirma su entrega.</p>
            </div>
            <span class="header-icon"><i class="las la-dolly-flatbed"></i></span>
        </div>

        {{-- ── Stats ── --}}
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon warehouse"><i class="las la-warehouse"></i></div>
                <div>
                    <div class="stat-value">{{ $stats['warehouse'] }}</div>
                    <div class="stat-label">En almacén — por despachar</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon on_the_way"><i class="las la-shipping-fast"></i></div>
                <div>
                    <div class="stat-value">{{ $stats['on_the_way'] }}</div>
                    <div class="stat-label">En camino al cliente</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon delivered"><i class="las la-check-circle"></i></div>
                <div>
                    <div class="stat-value">{{ $stats['delivered_today'] }}</div>
                    <div class="stat-label">Entregados hoy</div>
                </div>
            </div>
        </div>

        {{-- ── Alertas ── --}}
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

        {{-- ── Llegadas nuevas (notificaciones sin leer) ── --}}
        @if($arrivals->isNotEmpty())
            <div class="arrivals-box">
                <h6><i class="las la-bell"></i> Nuevas llegadas al almacén ({{ $arrivals->count() }})</h6>
                <ul>
                    @foreach($arrivals as $arrival)
                        <li>
                            <i class="las la-box"></i>
                            {{ $arrival->data['message'] }}
                            <span style="margin-left:auto; color:#a08c40; font-size:.75rem;">
                                {{ $arrival->created_at->diffForHumans() }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ── Tabla ── --}}
        <div class="orders-panel">
            <div class="orders-panel-header">
                <h5>Pedidos en gestión de almacén</h5>
                <form method="GET" action="{{ route('warehouse.index') }}" class="filter-bar">
                    <input type="text" name="code" value="{{ request('code') }}" placeholder="Código de pedido">
                    <select name="delivery_status" onchange="this.form.submit()">
                        <option value="">Activos (almacén + en camino)</option>
                        <option value="warehouse"  @selected(request('delivery_status') === 'warehouse')>En almacén</option>
                        <option value="on_the_way" @selected(request('delivery_status') === 'on_the_way')>En camino</option>
                        <option value="delivered"  @selected(request('delivery_status') === 'delivered')>Entregados</option>
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
                            <th>Dirección de envío</th>
                            <th>Llegó al almacén</th>
                            <th>Despachado</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $i => $order)
                            @php
                                $addr = is_array($order->shipping_address) ? $order->shipping_address : null;
                            @endphp
                            <tr>
                                <td>{{ $orders->firstItem() + $i }}</td>
                                <td style="font-weight:600;">{{ $order->code }}</td>
                                <td>{{ $addr['full_name'] ?? $order->user->name ?? '—' }}</td>
                                <td class="address-cell">
                                    @if($addr && ! empty($addr['address']))
                                        {{ $addr['address'] }}{{ ! empty($addr['city']) ? ', ' . $addr['city'] : '' }}
                                        {{ $addr['state'] ?? '' }} {{ $addr['country'] ?? '' }}
                                        @if(! empty($addr['phone']))
                                            <br><i class="las la-phone"></i> {{ $addr['phone'] }}
                                        @endif
                                        @if(! empty($addr['email']))
                                            <br><i class="las la-envelope"></i> {{ $addr['email'] }}
                                        @endif
                                    @else
                                        <span style="color:#e74c3c;"><i class="las la-exclamation-triangle"></i> Sin datos de envío</span>
                                    @endif
                                </td>
                                <td style="white-space:nowrap; color:#888;">
                                    {{ $order->warehouse_at?->format('d-m-Y H:i') ?? '—' }}
                                </td>
                                <td style="white-space:nowrap; color:#888;">
                                    {{ $order->dispatched_at?->format('d-m-Y H:i') ?? '—' }}
                                </td>
                                <td style="font-weight:600;">${{ number_format($order->grand_total, 2) }}</td>
                                <td>
                                    <span class="badge-status badge-{{ $order->delivery_status }}">
                                        {{ ucwords(str_replace('_', ' ', $order->delivery_status)) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center" style="gap:10px;">
                                        @if($order->delivery_status === 'warehouse')
                                            <form method="POST" action="{{ route('warehouse.dispatch', $order->id) }}"
                                                  onsubmit="return confirm('¿Despachar el pedido #{{ $order->code }}? El cliente será notificado de que va en camino.')">
                                                @csrf
                                                <button type="submit" class="btn-dispatch">
                                                    <i class="las la-shipping-fast"></i> Despachar
                                                </button>
                                            </form>
                                        @elseif($order->delivery_status === 'on_the_way')
                                            <form method="POST" action="{{ route('warehouse.deliver', $order->id) }}"
                                                  onsubmit="return confirm('¿Marcar el pedido #{{ $order->code }} como entregado?')">
                                                @csrf
                                                <button type="submit" class="btn-deliver">
                                                    <i class="las la-check"></i> Entregado
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ route('orders.show', $order->id) }}" class="btn-view">
                                            Ver <i class="las la-arrow-right"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="nothing-found">
                                        <i class="las la-dolly"></i>
                                        <span>No hay pedidos en el almacén por ahora</span>
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
</section>
@endsection
