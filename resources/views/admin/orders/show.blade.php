@extends('layouts.admin')

@section('title', __('Pedido #:code', ['code' => $order->code ?? $order->id]))

@section('content')
@php
    $shipping = $order->shippingInfo();
    $steps = [
        ['label' => __('Pagado'), 'at' => $order->paid_at],
        ['label' => delivery_status_label('confirmed'), 'at' => $order->confirmed_at],
        ['label' => delivery_status_label('warehouse'), 'at' => $order->warehouse_at],
        ['label' => delivery_status_label('on_the_way'), 'at' => $order->dispatched_at],
        ['label' => delivery_status_label('delivered'), 'at' => $order->delivered_at],
    ];
@endphp
<div class="container pb-4">

    <div class="admin-page-title">
        <h1>
            <a href="{{ route('admin.orders.index') }}" class="text-muted mr-1" title="{{ __('Volver') }}"><i class="las la-arrow-left"></i></a>
            {{ __('Pedido #:code', ['code' => $order->code ?? $order->id]) }}
        </h1>
        <div class="d-flex flex-wrap" style="gap:.4rem;">
            <span class="pill {{ $order->payment_status === 'paid' ? 'pill-ok' : 'pill-muted' }}">{{ payment_status_label($order->payment_status) }}</span>
            <span class="pill {{ $order->delivery_status === 'delivered' ? 'pill-ok' : 'pill-muted' }}">{{ delivery_status_label($order->delivery_status) }}</span>
            @if(in_array($order->delivery_status, ['warehouse', 'on_the_way'], true))
                <a href="{{ route('admin.warehouse.index', ['code' => $order->code]) }}" class="btn btn-sm btn-light"><i class="las la-warehouse"></i> {{ __('Gestionar en el almacén') }}</a>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="admin-card">
                <div class="admin-card-body">
                    <h2>{{ __('Artículos') }}</h2>
                    <div class="table-responsive">
                        <table class="table table-admin">
                            <thead>
                                <tr>
                                    <th>{{ __('Producto') }}</th>
                                    <th>{{ __('Tienda') }}</th>
                                    <th>{{ __('Cant.') }}</th>
                                    <th>{{ __('Precio') }}</th>
                                    <th>{{ __('Entrega') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->orderDetails as $detail)
                                    <tr>
                                        <td>
                                            @if($detail->product)
                                                <a href="{{ route('admin.products.show', $detail->product_id) }}" class="text-reset fw-600">{{ $detail->product->name }}</a>
                                            @else
                                                <span class="fw-600">{{ $detail->product_name }}</span>
                                            @endif
                                            <div>@include('partials.variant-badge', ['parts' => $detail->variant_parts, 'small' => true])</div>
                                        </td>
                                        <td>{{ $detail->seller?->shop?->name ?? $detail->seller?->name ?? '—' }}</td>
                                        <td>{{ $detail->quantity }}</td>
                                        <td>${{ number_format($detail->price, 2) }}</td>
                                        <td>
                                            {{ delivery_status_label($detail->delivery_status) }}
                                            @if($detail->settlement_id)
                                                <span class="pill pill-ok" title="{{ __('Incluida en una liquidación') }}">{{ __('Liquidada') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <dl class="dl-admin mt-3">
                        <dt>{{ __('Subtotal') }}</dt><dd>${{ number_format($order->subtotal, 2) }}</dd>
                        <dt>{{ __('Impuestos') }}</dt><dd>${{ number_format($order->tax_amount, 2) }}</dd>
                        <dt>{{ __('Envío') }}</dt><dd>${{ number_format($order->shipping_total, 2) }}</dd>
                        @if($order->discount_amount > 0)
                            <dt>{{ __('Descuento') }}</dt><dd>-${{ number_format($order->discount_amount, 2) }}</dd>
                        @endif
                        <dt>{{ __('Total') }}</dt><dd class="fw-700">${{ number_format($order->grand_total, 2) }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="admin-card">
                <div class="admin-card-body">
                    <h2>{{ __('Cliente y envío') }}</h2>
                    <dl class="dl-admin">
                        <dt>{{ __('Nombre') }}</dt><dd>{{ $shipping['full_name'] ?? $order->user?->name ?? '—' }}</dd>
                        <dt>{{ __('Correo') }}</dt><dd>{{ $shipping['email'] ?? $order->user?->email ?? '—' }}</dd>
                        <dt>{{ __('Teléfono') }}</dt><dd>{{ $shipping['phone'] ?? '—' }}</dd>
                        <dt>{{ __('Dirección') }}</dt><dd>{{ $shipping['address'] ?? '—' }}</dd>
                        <dt>{{ __('Ciudad') }}</dt><dd>{{ $shipping['city'] ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-body">
                    <h2>{{ __('Pago') }}</h2>
                    <dl class="dl-admin">
                        <dt>{{ __('Pasarela') }}</dt><dd>{{ $order->payment_gateway ? ucfirst($order->payment_gateway) : '—' }}</dd>
                        <dt>{{ __('Referencia') }}</dt><dd class="text-muted">{{ $order->payment_reference ?: '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-body">
                    <h2>{{ __('Seguimiento') }}</h2>
                    <dl class="dl-admin">
                        <dt>{{ __('Creado') }}</dt><dd>{{ $order->created_at?->format('d/m/Y H:i') }}</dd>
                        @foreach($steps as $step)
                            <dt>{{ $step['label'] }}</dt>
                            <dd>{{ $step['at'] ? \Illuminate\Support\Carbon::parse($step['at'])->format('d/m/Y H:i') : '—' }}</dd>
                        @endforeach
                        @if($order->dispatcher)
                            <dt>{{ __('Despachó') }}</dt><dd>{{ $order->dispatcher->name }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
