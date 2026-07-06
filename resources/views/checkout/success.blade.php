@extends('layouts.app')

@section('title', $order->isPaid() ? 'Pago Confirmado' : 'Procesando Pago')

@section('extra_css')
<style>
    body { background: #f5f6f8; }
    .success-page { padding: 50px 0 70px; }
    .success-card { background: #fff; border-radius: 10px; box-shadow: 0 1px 8px rgba(0,0,0,.07); padding: 40px 32px; max-width: 520px; margin: 0 auto; text-align: center; }
    .success-icon { width: 64px; height: 64px; margin: 0 auto 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; }
    .success-icon.ok   { background: #eef6e8; color: #679941; }
    .success-icon.wait { background: #fff7dc; color: #c79a00; }
    .success-card h2 { font-size: 1.15rem; font-weight: 700; color: #222; margin-bottom: 8px; }
    .success-card p  { font-size: .88rem; color: #888; }
    .order-detail { margin: 24px 0; border: 1px solid #f0f0f0; border-radius: 8px; padding: 16px; text-align: left; font-size: .84rem; }
    .order-detail .d-row { display: flex; justify-content: space-between; padding: 4px 0; color: #666; }
    .order-detail .d-row span:last-child { font-weight: 600; color: #333; }
    .order-detail .d-items { margin-top: 10px; padding-top: 10px; border-top: 1px dashed #eee; }
    .btn-shop { display: inline-block; background: #679941; color: #fff; border-radius: 8px; padding: 12px 30px; font-size: .9rem; font-weight: 700; text-decoration: none; transition: background .2s; }
    .btn-shop:hover { background: #4e7a2e; color: #fff; text-decoration: none; }
</style>
@endsection

@section('content')
<div class="success-page">
    <div class="container">
        <div class="success-card">

            @if($order->isPaid())
                <div class="success-icon ok"><i class="las la-check"></i></div>
                <h2>¡Pago confirmado!</h2>
                <p>Gracias por tu compra. Recibimos tu pago correctamente.</p>
            @else
                <div class="success-icon wait"><i class="las la-hourglass-half"></i></div>
                <h2>Confirmando tu pago…</h2>
                <p>Esto toma solo unos segundos. La página se actualizará automáticamente.</p>
                <script>setTimeout(() => location.reload(), 4000);</script>
            @endif

            <div class="order-detail">
                <div class="d-row"><span>Pedido</span><span>#{{ $order->code }}</span></div>
                <div class="d-row"><span>Total</span><span>${{ number_format($order->grand_total, 2) }}</span></div>
                @if($order->payment_reference)
                    <div class="d-row"><span>Referencia</span><span>{{ $order->payment_reference }}</span></div>
                @endif

                @if($order->orderDetails->count() > 0)
                    <div class="d-items">
                        @foreach($order->orderDetails as $item)
                            <div class="d-row">
                                <span>{{ $item->product_name }} × {{ $item->quantity }}</span>
                                <span>${{ number_format($item->price * $item->quantity, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            @auth
                <a href="{{ route('orders.index') }}" class="btn-shop" style="margin-right:8px;">
                    <i class="las la-file-invoice mr-1"></i> Mis Pedidos
                </a>
            @endauth
            <a href="{{ url('/products') }}" class="btn-shop">
                <i class="las la-store mr-1"></i> Seguir comprando
            </a>

        </div>
    </div>
</div>
@endsection
