@extends('layouts.app')

@section('title', 'Pedido ' . $order->code)

@section('extra_css')
<style>
    body { background: #f2f3f8; }
    .order-detail-page { padding: 28px 0 50px; }

    /* ── Tracker de pasos ── */
    .tracker-wrap {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,.07);
        padding: 28px 32px 24px;
        margin-bottom: 24px;
    }
    .tracker-title {
        font-size: 1rem;
        font-weight: 700;
        color: #222;
        margin-bottom: 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .tracker-title .close-btn {
        color: #aaa;
        font-size: 1.4rem;
        text-decoration: none;
        line-height: 1;
    }
    .tracker-title .close-btn:hover { color: #555; }

    /* Steps */
    .tracker-steps {
        display: flex;
        align-items: flex-start;
        position: relative;
    }
    .tracker-steps::before {
        content: '';
        position: absolute;
        top: 22px;
        left: 0; right: 0;
        height: 3px;
        background: #e0e0e0;
        z-index: 0;
    }
    .tracker-step {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 1;
    }
    .tracker-step-icon {
        width: 46px; height: 46px;
        border-radius: 50%;
        background: #e8eaed;
        border: 3px solid #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: #bbb;
        margin-bottom: 8px;
        transition: all .3s;
        position: relative;
    }
    .tracker-step.done .tracker-step-icon {
        background: #679941;
        border-color: #679941;
        color: #fff;
    }
    .tracker-step.active .tracker-step-icon {
        background: #fff;
        border-color: #679941;
        color: #679941;
        box-shadow: 0 0 0 4px rgba(103,153,65,.15);
    }
    /* line between steps (colored when done) */
    .tracker-step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 22px;
        left: 50%;
        width: 100%;
        height: 3px;
        background: #e0e0e0;
        z-index: 0;
    }
    .tracker-step.done:not(:last-child)::after { background: #679941; }

    .tracker-step-label {
        font-size: .75rem;
        font-weight: 600;
        color: #aaa;
        text-align: center;
        margin-top: 2px;
    }
    .tracker-step.done .tracker-step-label  { color: #679941; }
    .tracker-step.active .tracker-step-label { color: #679941; }

    .tracker-step-date {
        font-size: .68rem;
        color: #999;
        margin-top: 2px;
        text-align: center;
    }

    /* ── Info cards ── */
    .info-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,.07);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .info-card-header {
        padding: 14px 20px;
        border-bottom: 1px solid #f0f0f0;
        font-size: .9rem;
        font-weight: 700;
        color: #333;
    }
    .info-card-body { padding: 20px; }

    /* ── Summary grid ── */
    .summary-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px 32px;
    }
    @media (max-width: 576px) { .summary-grid { grid-template-columns: 1fr; } }

    .summary-item {}
    .summary-item .label {
        font-size: .75rem;
        font-weight: 700;
        color: #888;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: 3px;
    }
    .summary-item .value {
        font-size: .87rem;
        color: #333;
        font-weight: 500;
    }

    /* ── Badges ── */
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

    /* ── Seller warehouse action card ── */
    .warehouse-action-card {
        background: linear-gradient(135deg, #f5f0ff 0%, #ede8ff 100%);
        border: 2px solid #c9b8f0;
        border-radius: 12px;
        padding: 22px 24px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }
    .warehouse-action-info { flex: 1; min-width: 200px; }
    .warehouse-action-info h6 {
        font-size: .95rem; font-weight: 700; color: #3d1a6e; margin: 0 0 4px;
    }
    .warehouse-action-info p {
        font-size: .8rem; color: #6b4fa0; margin: 0;
    }
    .btn-warehouse {
        display: inline-flex; align-items: center; gap: 8px;
        background: #6f42c1; color: #fff;
        border: none; border-radius: 8px;
        padding: 10px 22px; font-size: .875rem; font-weight: 700;
        cursor: pointer; transition: background .2s, transform .1s;
        text-decoration: none; white-space: nowrap;
    }
    .btn-warehouse:hover { background: #5a34a0; color: #fff; transform: translateY(-1px); }
    .btn-warehouse:active { transform: translateY(0); }
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

    /* ── Details table ── */
    .details-table { width:100%; border-collapse:collapse; font-size:.83rem; }
    .details-table thead tr { background:#f8f9fa; }
    .details-table thead th { padding:10px 14px; font-weight:700; color:#555; border-bottom:2px solid #eee; white-space:nowrap; }
    .details-table tbody tr { border-bottom:1px solid #f4f4f4; }
    .details-table tbody tr:last-child { border-bottom:none; }
    .details-table tbody td { padding:12px 14px; color:#444; vertical-align:middle; }
    .product-link { color:#679941; text-decoration:none; }
    .product-link:hover { text-decoration:underline; }

    /* ── Totals box ── */
    .totals-box { padding: 0; }
    .totals-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #f4f4f4;
        font-size: .85rem;
        color: #555;
    }
    .totals-row:last-child {
        border-bottom: none;
        font-weight: 700;
        font-size: .95rem;
        color: #222;
        padding-top: 14px;
    }
    .totals-row .t-label { color: #888; }

    /* ── Back button ── */
    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 8px 18px;
        font-size: .85rem;
        font-weight: 600;
        color: #444;
        text-decoration: none;
        margin-bottom: 20px;
        transition: background .2s;
    }
    .btn-back:hover { background: #f5f5f5; color: #333; text-decoration: none; }
</style>
@endsection

@section('content')
<div class="order-detail-page">
    <div class="container">

        <a href="{{ route('orders.index') }}" class="btn-back">
            <i class="las la-arrow-left"></i> Volver a mis pedidos
        </a>

        {{-- ── Alertas de acción almacén ── --}}
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

        {{-- ══════════════════════════════════
             ACCIÓN: ENVIAR AL ALMACÉN (solo vendedor)
        ══════════════════════════════════ --}}
        @if(isset($isSeller) && $isSeller && $order->delivery_status === 'confirmed')
        <div class="warehouse-action-card">
            <div class="warehouse-action-info">
                <h6><i class="las la-warehouse mr-1"></i> Enviar pedido al almacén</h6>
                <p>El pedido está confirmado y listo para prepararse. Envíalo al almacén para iniciar el despacho.</p>
            </div>
            <form method="POST" action="{{ route('orders.send-to-warehouse', $order->id) }}"
                  onsubmit="return confirm('¿Confirmar envío del pedido #{{ $order->code }} al almacén?')">
                @csrf
                <button type="submit" class="btn-warehouse">
                    <i class="las la-warehouse"></i> Enviar al almacén
                </button>
            </form>
        </div>
        @endif

        {{-- Estado: ya en almacén (informativo) --}}
        @if(isset($isSeller) && $isSeller && $order->delivery_status === 'warehouse')
        <div class="warehouse-action-card" style="background:linear-gradient(135deg,#edfaf0,#d4f5dc);border-color:#7ed4a0;">
            <div class="warehouse-action-info">
                <h6 style="color:#1a5c32;"><i class="las la-check-circle mr-1"></i> Pedido en almacén</h6>
                <p style="color:#2d7a4a;">El pedido ya fue enviado al almacén y está pendiente de despacho al cliente.</p>
            </div>
            <span style="font-size:2rem; color:#2d7a4a;"><i class="las la-warehouse"></i></span>
        </div>
        @endif

        {{-- ══════════════════════════════════
             TRACKER DE SEGUIMIENTO
        ══════════════════════════════════ --}}
        <div class="tracker-wrap">
            <div class="tracker-title">
                <span>Solicitar ID: {{ $order->code }}</span>
                <a href="{{ route('orders.index') }}" class="close-btn" title="Cerrar">&times;</a>
            </div>

            @php
                $steps = [
                    'pending'    => ['label' => 'Pedido realizado', 'icon' => 'las la-file-alt',        'date' => $order->created_at],
                    'confirmed'  => ['label' => 'Confirmado',       'icon' => 'las la-clipboard-check', 'date' => $order->confirmed_at],
                    'warehouse'  => ['label' => 'En almacén',       'icon' => 'las la-warehouse',       'date' => $order->warehouse_at],
                    'on_the_way' => ['label' => 'En camino',        'icon' => 'las la-shipping-fast',   'date' => $order->dispatched_at],
                    'delivered'  => ['label' => 'Entregado',        'icon' => 'las la-check-circle',    'date' => $order->delivered_at],
                ];

                // Orden de los estados
                $stepOrder = ['pending', 'confirmed', 'warehouse', 'on_the_way', 'delivered'];
                $currentIndex = array_search($order->delivery_status, $stepOrder);
                if ($currentIndex === false) $currentIndex = -1;
            @endphp

            <div class="tracker-steps">
                @foreach($steps as $key => $step)
                    @php
                        $stepIdx = array_search($key, $stepOrder);
                        $isDone   = $stepIdx < $currentIndex;
                        $isActive = $stepIdx === $currentIndex;
                        $cls = $isDone ? 'done' : ($isActive ? 'active' : '');
                    @endphp
                    <div class="tracker-step {{ $cls }}">
                        <div class="tracker-step-icon">
                            <i class="{{ $step['icon'] }}"></i>
                        </div>
                        <span class="tracker-step-label">{{ $step['label'] }}</span>
                        @if(($isDone || $isActive) && $step['date'])
                            <span class="tracker-step-date">{{ $step['date']->format('d-m-Y H:i') }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ══════════════════════════════════
             RESUMEN DEL PEDIDO
        ══════════════════════════════════ --}}
        @php
            $addressData = is_array($order->shipping_address) && count($order->shipping_address)
                ? $order->shipping_address
                : null;

            // Vendedor del pedido y admin (almacén) ven los datos completos;
            // en la vista del cliente se enmascaran.
            $staffView = Auth::user()->user_type === 'admin' || ($isSeller ?? false);

            $buyerName  = $addressData['full_name'] ?? $order->user->name ?? '—';
            $buyerEmail = $addressData['email'] ?? $order->user->email ?? null;
            $buyerPhone = $addressData['phone'] ?? null;
        @endphp

        <div class="info-card">
            <div class="info-card-header">
                <i class="las la-receipt mr-2" style="color:#679941;"></i> Resumen del pedido
            </div>
            <div class="info-card-body">
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="label">Código de orden</div>
                        <div class="value">{{ $order->code }}</div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Fecha de orden</div>
                        <div class="value">{{ $order->created_at->format('d-m-Y H:i A') }}</div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Cliente</div>
                        <div class="value">
                            @if($staffView)
                                {{ $buyerName }}
                            @else
                                {{ substr($buyerName, 0, 2) . str_repeat('*', max(0, strlen($buyerName) - 3)) . substr($buyerName, -1) }}
                            @endif
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Estado del pedido</div>
                        <div class="value">
                            <span class="badge-status badge-{{ $order->delivery_status }}">
                                {{ ucwords(str_replace('_', ' ', $order->delivery_status)) }}
                            </span>
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Email</div>
                        <div class="value">
                            @if(! $buyerEmail)
                                —
                            @elseif($staffView)
                                {{ $buyerEmail }}
                            @else
                                @php
                                    $parts = explode('@', $buyerEmail);
                                @endphp
                                {{ substr($parts[0], 0, 2) . str_repeat('*', max(0, strlen($parts[0]) - 2)) . '@' . ($parts[1] ?? '') }}
                            @endif
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Importe total del pedido</div>
                        <div class="value" style="font-weight:700; color:#679941; font-size:1rem;">
                            ${{ number_format($order->grand_total, 2) }}
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Dirección de Envío</div>
                        <div class="value">
                            @if($addressData && ! empty($addressData['address']))
                                {{ $addressData['address'] }}{{ ! empty($addressData['city']) ? ', ' . $addressData['city'] : '' }}
                                {{ $addressData['state'] ?? '' }}
                                {{ $addressData['country'] ?? '' }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Teléfono</div>
                        <div class="value">
                            @if(! $buyerPhone)
                                —
                            @elseif($staffView)
                                {{ $buyerPhone }}
                            @else
                                {{ substr($buyerPhone, 0, 3) . str_repeat('*', max(0, strlen($buyerPhone) - 5)) . substr($buyerPhone, -2) }}
                            @endif
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Método de pago</div>
                        <div class="value">{{ ucfirst($order->payment_type ?? 'No especificado') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════
             DETALLES DEL PEDIDO + TOTALES
        ══════════════════════════════════ --}}
        <div class="row gutters-10">

            {{-- Productos --}}
            <div class="col-lg-8 mb-4">
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="las la-box mr-2" style="color:#679941;"></i> Detalles del pedido
                    </div>
                    <div class="table-responsive">
                        <table class="details-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Producto</th>
                                    <th>Variación</th>
                                    <th>Cantidad</th>
                                    <th>Tipo de entrega</th>
                                    <th>Precio</th>
                                    <th>Reembolso</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->orderDetails as $i => $detail)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            @if($detail->product)
                                                <a href="{{ url('/product/' . $detail->product->slug) }}"
                                                   class="product-link">
                                                    {{ $detail->product->name }}
                                                </a>
                                            @else
                                                <span style="color:#bbb;">Producto eliminado</span>
                                            @endif
                                        </td>
                                        <td>{{ $detail->variation ?? '—' }}</td>
                                        <td>{{ $detail->quantity }}</td>
                                        <td>
                                            <span class="badge-status badge-{{ $detail->delivery_status }}">
                                                {{ ucwords(str_replace('_', ' ', $detail->delivery_status)) }}
                                            </span>
                                        </td>
                                        <td style="font-weight:600;">
                                            ${{ number_format($detail->price * $detail->quantity, 2) }}
                                        </td>
                                        <td style="color:#aaa;">N / A</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Totales --}}
            <div class="col-lg-4 mb-4">
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="las la-calculator mr-2" style="color:#679941;"></i> Orden Amount
                    </div>
                    <div class="info-card-body">
                        @php
                            $subtotal      = $order->orderDetails->sum(fn($d) => $d->price * $d->quantity);
                            $shippingTotal = $order->orderDetails->sum('shipping_cost');
                            $taxTotal      = $order->orderDetails->sum('tax');
                            $coupon        = $order->coupon_discount ?? 0;
                        @endphp
                        <div class="totals-box">
                            <div class="totals-row">
                                <span class="t-label">Total parcial</span>
                                <span>${{ number_format($subtotal, 2) }}</span>
                            </div>
                            <div class="totals-row">
                                <span class="t-label">Envío</span>
                                <span>${{ number_format($shippingTotal, 2) }}</span>
                            </div>
                            <div class="totals-row">
                                <span class="t-label">Impuesto</span>
                                <span>${{ number_format($taxTotal, 2) }}</span>
                            </div>
                            <div class="totals-row">
                                <span class="t-label">Cupón</span>
                                <span>-${{ number_format($coupon, 2) }}</span>
                            </div>
                            <div class="totals-row">
                                <span>TOTAL</span>
                                <span style="color:#679941;">${{ number_format($order->grand_total, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection