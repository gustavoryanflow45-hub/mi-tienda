@extends('layouts.app')

@section('title', __('Rastrear pedido'))

@section('content')
<div style="background:#f2f3f8; padding:30px 0 60px; min-height:60vh;">
    <div class="container" style="max-width:600px;">

        <h1 style="font-size:1.2rem; font-weight:700; color:#222; margin-bottom:24px;">
            <i class="las la-shipping-fast" style="color:#679941;"></i> {{ __('Rastrear pedido') }}
        </h1>

        <div style="background:#fff; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,.07); padding:28px;">
            <form method="GET" action="{{ route('track-order') }}">
                <label style="font-size:.85rem; font-weight:600; color:#555; display:block; margin-bottom:8px;">{{ __('Código de pedido') }}</label>
                <div style="display:flex; gap:10px;">
                    <input type="text" name="code" value="{{ request('code') }}" placeholder="ORD-20260610-XXXXXX" required
                        style="flex:1; border:1px solid #dde2e8; border-radius:6px; padding:10px 14px; font-size:.88rem; outline:none;">
                    <button type="submit"
                        style="background:#679941; color:#fff; border:none; border-radius:6px; padding:10px 20px; font-size:.88rem; font-weight:600; cursor:pointer; white-space:nowrap;">
                        {{ __('Rastrear') }}
                    </button>
                </div>
            </form>

            @if(request('code') && !$order)
                <div style="margin-top:20px; background:#fff3cd; color:#856404; border-radius:6px; padding:12px 16px; font-size:.85rem;">
                    {{ __('No se encontró ningún pedido con ese código.') }}
                </div>
            @endif

            @if($order)
                <div style="margin-top:24px; border:1px solid #f0f0f0; border-radius:8px; padding:20px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:12px;">
                        <span style="font-size:.83rem; color:#666;">{{ __('Pedido') }}</span>
                        <span style="font-size:.83rem; font-weight:700; color:#333;">{{ $order->code }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:12px;">
                        <span style="font-size:.83rem; color:#666;">{{ __('Estado de pago') }}</span>
                        <span style="font-size:.83rem; font-weight:600;
                            color:{{ $order->payment_status === 'paid' ? '#155724' : '#856404' }}">
                            {{ $order->payment_status === 'paid' ? __('Pagado') : __('Pendiente') }}
                        </span>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:12px;">
                        <span style="font-size:.83rem; color:#666;">{{ __('Estado de entrega') }}</span>
                        <span style="font-size:.83rem; font-weight:600; color:#333;">
                            {{ delivery_status_label($order->delivery_status) }}
                        </span>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span style="font-size:.83rem; color:#666;">{{ __('Total') }}</span>
                        <span style="font-size:.9rem; font-weight:700; color:#679941;">${{ number_format($order->grand_total, 2) }}</span>
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
