@extends('layouts.admin')

@section('title', __('Pedidos'))

@section('content')
@php
    $deliveryStatuses = ['pending', 'confirmed', 'warehouse', 'on_the_way', 'delivered'];
    $paymentStatuses = ['paid', 'unpaid'];
@endphp
<div class="container pb-4">

    <div class="admin-page-title">
        <h1>{{ __('Pedidos') }}</h1>
        @can('warehouse.view')
            <a href="{{ route('admin.warehouse.index') }}" class="btn btn-sm btn-light"><i class="las la-warehouse"></i> {{ __('Ir al almacén') }}</a>
        @endcan
    </div>

    <div class="admin-card">
        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.orders.index') }}" class="admin-filters">
                <input type="search" name="code" value="{{ request('code') }}" class="form-control" placeholder="{{ __('Código del pedido') }}" aria-label="{{ __('Código del pedido') }}">
                <select name="payment_status" class="form-control" aria-label="{{ __('Pago') }}">
                    <option value="">{{ __('Cualquier pago') }}</option>
                    @foreach($paymentStatuses as $status)
                        <option value="{{ $status }}" @selected(request('payment_status') === $status)>{{ payment_status_label($status) }}</option>
                    @endforeach
                </select>
                <select name="delivery_status" class="form-control" aria-label="{{ __('Entrega') }}">
                    <option value="">{{ __('Cualquier entrega') }}</option>
                    @foreach($deliveryStatuses as $status)
                        <option value="{{ $status }}" @selected(request('delivery_status') === $status)>{{ delivery_status_label($status) }}</option>
                    @endforeach
                </select>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" aria-label="{{ __('Desde') }}">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" aria-label="{{ __('Hasta') }}">
                <button type="submit" class="btn btn-primary btn-sm px-3">{{ __('Filtrar') }}</button>
                @if(request()->hasAny(['code', 'payment_status', 'delivery_status', 'date_from', 'date_to']))
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-light btn-sm">{{ __('Limpiar') }}</a>
                @endif
            </form>
        </div>
    </div>

    <div class="admin-card">
        <div class="table-responsive">
            <table class="table table-admin">
                <thead>
                    <tr>
                        <th>{{ __('Pedido') }}</th>
                        <th>{{ __('Fecha') }}</th>
                        <th>{{ __('Cliente') }}</th>
                        <th>{{ __('Artículos') }}</th>
                        <th>{{ __('Total') }}</th>
                        <th>{{ __('Pago') }}</th>
                        <th>{{ __('Entrega') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td><a href="{{ route('admin.orders.show', $order->id) }}" class="fw-600">#{{ $order->code ?? $order->id }}</a></td>
                            <td class="text-nowrap">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $order->shippingInfo()['full_name'] ?? $order->user?->name ?? '—' }}</td>
                            <td>{{ $order->order_details_count }}</td>
                            <td>${{ number_format($order->grand_total, 2) }}</td>
                            <td><span class="pill {{ $order->payment_status === 'paid' ? 'pill-ok' : 'pill-muted' }}">{{ payment_status_label($order->payment_status) }}</span></td>
                            <td><span class="pill {{ $order->delivery_status === 'delivered' ? 'pill-ok' : 'pill-muted' }}">{{ delivery_status_label($order->delivery_status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No hay pedidos con esos filtros.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $orders->links() }}
</div>
@endsection
