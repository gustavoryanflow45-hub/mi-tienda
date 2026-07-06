@extends('layouts.app')

@section('title', 'Mis Pedidos')

@section('extra_css')
<style>
    body { background: #f2f3f8; }

    /* ── Page layout ── */
    .orders-page { padding: 28px 0 50px; }

    /* ── Header card ── */
    .page-header-card {
        background: linear-gradient(135deg, #679941, #4e7a2e);
        border-radius: 12px;
        padding: 22px 28px;
        color: #fff;
        margin-bottom: 24px;
    }
    .page-header-card h2 { font-size: 1.25rem; font-weight: 700; margin: 0 0 4px; }
    .page-header-card p  { font-size: .85rem; opacity: .85; margin: 0; }

    /* ── Filter bar ── */
    .filter-bar {
        background: #fff;
        border-radius: 10px;
        padding: 16px 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,.06);
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: flex-end;
    }
    .filter-group { display: flex; flex-direction: column; gap: 4px; }
    .filter-group label { font-size: .75rem; font-weight: 600; color: #777; }
    .filter-control {
        border: 1px solid #dde2e8;
        border-radius: 6px;
        padding: 6px 12px;
        font-size: .83rem;
        color: #333;
        height: 36px;
        background: #fafbfc;
        outline: none;
        min-width: 140px;
    }
    .filter-control:focus { border-color: #679941; }
    .btn-filter {
        background: #679941;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 0 18px;
        height: 36px;
        font-size: .83rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .2s;
    }
    .btn-filter:hover { background: #4e7a2e; }
    .btn-reset {
        background: #f1f3f5;
        color: #555;
        border: none;
        border-radius: 6px;
        padding: 0 14px;
        height: 36px;
        font-size: .83rem;
        cursor: pointer;
        text-decoration: none;
        display: flex;
        align-items: center;
    }
    .btn-reset:hover { background: #e2e6ea; color: #333; text-decoration: none; }

    /* ── Orders table panel ── */
    .orders-panel {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,.06);
        overflow: hidden;
        margin-bottom: 20px;
    }
    .orders-panel-header {
        padding: 14px 20px;
        border-bottom: 1px solid #f0f0f0;
        font-size: .9rem;
        font-weight: 700;
        color: #333;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    /* ── Table ── */
    .orders-table { width: 100%; border-collapse: collapse; font-size: .83rem; }
    .orders-table thead tr { background: #f8f9fa; }
    .orders-table thead th { padding: 11px 16px; font-weight: 700; color: #555; white-space: nowrap; border-bottom: 2px solid #eee; }
    .orders-table tbody tr { border-bottom: 1px solid #f4f4f4; transition: background .15s; }
    .orders-table tbody tr:last-child { border-bottom: none; }
    .orders-table tbody tr:hover { background: #f9fbf7; }
    .orders-table tbody td { padding: 13px 16px; color: #444; vertical-align: middle; }

    /* expand toggle */
    .expand-btn {
        width: 22px; height: 22px;
        border-radius: 50%;
        border: 2px solid #679941;
        background: #fff;
        color: #679941;
        font-size: 13px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background .15s, color .15s;
        flex-shrink: 0;
    }
    .expand-btn:hover, .expand-btn.open { background: #679941; color: #fff; }

    .order-code-link { color: #1a73e8; text-decoration: none; font-weight: 500; }
    .order-code-link:hover { text-decoration: underline; }

    /* ── Badges ── */
    .badge-status {
        border-radius: 20px;
        padding: 3px 10px;
        font-size: 11px;
        font-weight: 700;
        display: inline-block;
    }
    .badge-pending    { background:#fff3cd; color:#856404; }
    .badge-confirmed  { background:#cce5ff; color:#004085; }
    .badge-warehouse  { background:#e8d5f5; color:#5a1e82; }
    .badge-on_the_way { background:#d1ecf1; color:#0c5460; }
    .badge-delivered  { background:#d4edda; color:#155724; }
    .badge-cancelled  { background:#f8d7da; color:#721c24; }
    .badge-returned   { background:#e2e3e5; color:#383d41; }
    .badge-paid       { background:#d4edda; color:#155724; }
    .badge-unpaid     { background:#f8d7da; color:#721c24; }
    .badge-partial    { background:#fff3cd; color:#856404; }

    /* ── Action buttons ── */
    .btn-action {
        width: 32px; height: 32px;
        border-radius: 50%;
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        text-decoration: none;
        transition: opacity .2s;
        font-size: 14px;
    }
    .btn-action:hover { opacity: .8; text-decoration: none; }
    .btn-view    { background: #e0f7f4; color: #0097a7; }
    .btn-notify  { background: #fff3e0; color: #f57c00; }
    .btn-invoice { background: #e8eaf6; color: #3949ab; }

    /* ── Sub-row (productos del pedido) ── */
    .sub-row { background: #f9fbf9; }
    .sub-row td { padding: 0 !important; }
    .sub-table-wrap { padding: 10px 16px 14px 52px; }
    .sub-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
    .sub-table th { padding: 6px 10px; color: #888; font-weight: 600; border-bottom: 1px solid #eee; }
    .sub-table td { padding: 7px 10px; color: #555; border-bottom: 1px solid #f5f5f5; }
    .sub-table tr:last-child td { border-bottom: none; }

    /* ── Empty state ── */
    .empty-orders { text-align: center; padding: 4rem 2rem; color: #bbb; }
    .empty-orders i { font-size: 3rem; display: block; margin-bottom: 1rem; }

    /* ── Pagination ── */
    .pagination-wrap { display: flex; justify-content: center; padding: 16px; gap: 4px; flex-wrap: wrap; }
    .page-btn { min-width:32px; height:32px; border:1px solid #ddd; border-radius:5px; background:#fff; color:#555; font-size:.82rem; font-weight:600; display:flex; align-items:center; justify-content:center; text-decoration:none; padding:0 8px; transition:all .15s; }
    .page-btn:hover { border-color:#679941; color:#679941; text-decoration:none; }
    .page-btn.active { background:#679941; border-color:#679941; color:#fff; }
    .page-btn.disabled { opacity:.4; pointer-events:none; }
</style>
@endsection

@section('content')
<div class="orders-page">
    <div class="container">

        {{-- Header --}}
        <div class="page-header-card">
            <h2><i class="las la-file-invoice mr-2"></i>Mis Pedidos</h2>
            <p>Revisa el estado y detalle de todos tus pedidos</p>
        </div>

        {{-- Avisos de estado nuevos --}}
        @if(isset($statusUpdates) && $statusUpdates->isNotEmpty())
            <div style="background:#eef7ff; border:1px solid #b8dcf5; border-radius:10px; padding:14px 18px; margin-bottom:20px;">
                <h6 style="font-size:.85rem; font-weight:700; color:#0b5394; margin:0 0 8px; display:flex; align-items:center; gap:6px;">
                    <i class="las la-bell"></i> Novedades de tus pedidos ({{ $statusUpdates->count() }})
                </h6>
                <ul style="margin:0; padding-left:0; list-style:none;">
                    @foreach($statusUpdates as $update)
                        <li style="font-size:.82rem; color:#155a8a; padding:4px 0; display:flex; align-items:center; gap:6px;">
                            <i class="las la-truck"></i>
                            <a href="{{ route('orders.show', $update->data['order_id']) }}"
                               style="color:#155a8a; text-decoration:none;">
                                {{ $update->data['message'] }}
                            </a>
                            <span style="margin-left:auto; color:#7fa8c9; font-size:.75rem;">
                                {{ $update->created_at->diffForHumans() }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Filtros --}}
        <form method="GET" action="{{ route('orders.index') }}" class="filter-bar">
            <div class="filter-group">
                <label>Estado de pago</label>
                <select name="payment_status" class="filter-control">
                    <option value="">Filtrar por estado...</option>
                    <option value="unpaid"  {{ request('payment_status')=='unpaid'  ? 'selected':'' }}>Sin pagar</option>
                    <option value="paid"    {{ request('payment_status')=='paid'    ? 'selected':'' }}>Pagado</option>
                    <option value="partial" {{ request('payment_status')=='partial' ? 'selected':'' }}>Parcial</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Estado de entrega</label>
                <select name="delivery_status" class="filter-control">
                    <option value="">Filtrar por estado...</option>
                    <option value="pending"    {{ request('delivery_status')=='pending'    ? 'selected':'' }}>Pendiente</option>
                    <option value="confirmed"  {{ request('delivery_status')=='confirmed'  ? 'selected':'' }}>Confirmado</option>
                    <option value="warehouse"  {{ request('delivery_status')=='warehouse'  ? 'selected':'' }}>En almacén</option>
                    <option value="on_the_way" {{ request('delivery_status')=='on_the_way' ? 'selected':'' }}>En camino</option>
                    <option value="delivered"  {{ request('delivery_status')=='delivered'  ? 'selected':'' }}>Entregado</option>
                    <option value="cancelled"  {{ request('delivery_status')=='cancelled'  ? 'selected':'' }}>Cancelado</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Fecha inicio</label>
                <input type="date" name="date_from" class="filter-control" value="{{ request('date_from') }}">
            </div>
            <div class="filter-group">
                <label>Fecha fin</label>
                <input type="date" name="date_to" class="filter-control" value="{{ request('date_to') }}">
            </div>
            <div class="filter-group">
                <label>Código de pedido</label>
                <input type="text" name="code" class="filter-control" placeholder="Escriba el código..." value="{{ request('code') }}">
            </div>
            <button type="submit" class="btn-filter">
                <i class="las la-search mr-1"></i> Filtrar
            </button>
            <a href="{{ route('orders.index') }}" class="btn-reset">
                <i class="las la-times mr-1"></i> Limpiar
            </a>
        </form>

        {{-- Tabla de pedidos --}}
        <div class="orders-panel">
            <div class="orders-panel-header">
                <span>Pedidos <span style="color:#aaa; font-weight:400; font-size:.8rem;">({{ $orders->total() }} total)</span></span>
            </div>

            @if($orders->count() > 0)
                <div class="table-responsive">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Código de orden</th>
                                <th>Ventas al por menor</th>
                                <th>Lucro</th>
                                <th>Estado de pago</th>
                                <th>Estado de entrega</th>
                                <th>Fecha</th>
                                <th>Opciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $i => $order)
                                {{-- Fila principal --}}
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <button class="expand-btn"
                                                    onclick="toggleRow({{ $order->id }}, this)"
                                                    title="Ver productos">+</button>
                                            {{ $orders->firstItem() + $i }}
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ route('orders.show', $order->id) }}"
                                           class="order-code-link">
                                            {{ $order->code }}
                                        </a>
                                    </td>
                                    <td><strong>${{ number_format($order->grand_total, 2) }}</strong></td>
                                    <td>
                                        @php
                                            $profit = $order->orderDetails->sum(function($d) {
                                                return ($d->price * $d->quantity) - $d->discount_on_product - $d->tax;
                                            });
                                        @endphp
                                        ${{ number_format($profit, 2) }}
                                    </td>
                                    <td>
                                        <span class="badge-status badge-{{ $order->payment_status }}">
                                            {{ ucfirst($order->payment_status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-status badge-{{ $order->delivery_status }}">
                                            {{ ucwords(str_replace('_', ' ', $order->delivery_status)) }}
                                        </span>
                                    </td>
                                    <td style="white-space:nowrap; color:#888; font-size:.78rem;">
                                        {{ $order->created_at->format('d-m-Y H:i') }}
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            {{-- Notificación --}}
                                            <span class="btn-action btn-notify" title="Notificar">
                                                <i class="las la-bell"></i>
                                            </span>
                                            {{-- Ver detalle --}}
                                            <a href="{{ route('orders.show', $order->id) }}"
                                               class="btn-action btn-view" title="Ver detalle">
                                                <i class="las la-eye"></i>
                                            </a>
                                            {{-- Factura --}}
                                            <span class="btn-action btn-invoice" title="Factura">
                                                <i class="las la-file-invoice"></i>
                                            </span>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Sub-fila expandible (productos del pedido) --}}
                                <tr class="sub-row" id="sub-{{ $order->id }}" style="display:none;">
                                    <td colspan="8">
                                        <div class="sub-table-wrap">
                                            <table class="sub-table">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Producto</th>
                                                        <th>Variación</th>
                                                        <th>Cantidad</th>
                                                        <th>Precio</th>
                                                        <th>Estado entrega</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($order->orderDetails as $j => $detail)
                                                        <tr>
                                                            <td>{{ $j + 1 }}</td>
                                                            <td>
                                                                @if($detail->product)
                                                                    <a href="{{ url('/product/' . $detail->product->slug) }}"
                                                                       style="color:#679941; text-decoration:none;">
                                                                        {{ Str::limit($detail->product->name, 40) }}
                                                                    </a>
                                                                @else
                                                                    <span style="color:#bbb;">Producto eliminado</span>
                                                                @endif
                                                            </td>
                                                            <td>{{ $detail->variation ?? '—' }}</td>
                                                            <td>{{ $detail->quantity }}</td>
                                                            <td>${{ number_format($detail->price, 2) }}</td>
                                                            <td>
                                                                <span class="badge-status badge-{{ $detail->delivery_status }}">
                                                                    {{ ucwords(str_replace('_', ' ', $detail->delivery_status)) }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Paginación --}}
                @if($orders->hasPages())
                    <div class="pagination-wrap">
                        @if($orders->onFirstPage())
                            <span class="page-btn disabled">&laquo;</span>
                        @else
                            <a href="{{ $orders->previousPageUrl() }}" class="page-btn">&laquo;</a>
                        @endif

                        @foreach($orders->getUrlRange(1, $orders->lastPage()) as $page => $url)
                            @if($page == $orders->currentPage())
                                <span class="page-btn active">{{ $page }}</span>
                            @elseif($page == 1 || $page == $orders->lastPage() || abs($page - $orders->currentPage()) <= 2)
                                <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
                            @elseif(abs($page - $orders->currentPage()) == 3)
                                <span class="page-btn disabled">…</span>
                            @endif
                        @endforeach

                        @if($orders->hasMorePages())
                            <a href="{{ $orders->nextPageUrl() }}" class="page-btn">&raquo;</a>
                        @else
                            <span class="page-btn disabled">&raquo;</span>
                        @endif
                    </div>
                @endif

            @else
                <div class="empty-orders">
                    <i class="las la-box-open"></i>
                    <p style="font-size:.95rem; font-weight:600; color:#888;">No tienes pedidos aún.</p>
                    <a href="{{ url('/') }}" style="color:#679941; font-size:.85rem;">← Ir a comprar</a>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection

@section('extra_js')
<script>
function toggleRow(id, btn) {
    const row = document.getElementById('sub-' + id);
    const isOpen = row.style.display !== 'none';
    row.style.display = isOpen ? 'none' : 'table-row';
    btn.textContent = isOpen ? '+' : '−';
    btn.classList.toggle('open', !isOpen);
}
</script>
@endsection