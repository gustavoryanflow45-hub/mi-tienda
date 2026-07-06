@extends('layouts.app')

@section('title', 'Checkout')

@section('extra_css')
<style>
    body { background: #f5f6f8; }
    .checkout-page { padding: 30px 0 60px; }

    .checkout-card { background: #fff; border-radius: 10px; box-shadow: 0 1px 8px rgba(0,0,0,.07); padding: 24px; }
    .checkout-card h3 { font-size: .95rem; font-weight: 700; color: #333; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0; }

    /* Items del resumen */
    .co-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
    .co-item:last-of-type { border-bottom: none; }
    .co-item-img { width: 48px; height: 48px; object-fit: cover; border-radius: 6px; background: #eee; flex-shrink: 0; }
    .co-item-img-ph { width: 48px; height: 48px; border-radius: 6px; background: #e8eaed; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .co-item-img-ph i { font-size: 1.2rem; color: #bbb; }
    .co-item-name { font-size: .82rem; font-weight: 600; color: #333; line-height: 1.3; }
    .co-item-meta { font-size: .75rem; color: #999; }
    .co-item-price { margin-left: auto; font-size: .85rem; font-weight: 600; color: #444; white-space: nowrap; }

    .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: .85rem; color: #666; }
    .summary-row.total { font-weight: 700; font-size: 1rem; color: #222; margin-top: 14px; padding-top: 14px; border-top: 1px solid #eee; }
    .summary-row.total .val { color: #679941; font-size: 1.1rem; }

    /* Stripe */
    #payment-element { min-height: 120px; }
    .stripe-loading { display: flex; align-items: center; justify-content: center; gap: 10px; color: #999; font-size: .85rem; padding: 40px 0; }
    .stripe-loading .spinner { width: 18px; height: 18px; border: 2px solid #ddd; border-top-color: #679941; border-radius: 50%; animation: spin .7s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    .btn-pay { display: block; width: 100%; background: #679941; color: #fff; border: none; border-radius: 8px; padding: 13px; font-size: .95rem; font-weight: 700; cursor: pointer; margin-top: 20px; letter-spacing: .3px; transition: background .2s; }
    .btn-pay:hover { background: #4e7a2e; }
    .btn-pay:disabled { opacity: .55; cursor: not-allowed; }

    #payment-error { margin-top: 12px; font-size: .82rem; color: #e74c3c; min-height: 18px; }

    .secure-note { display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 14px; font-size: .75rem; color: #aaa; }
    .test-badge { display: inline-block; margin-bottom: 14px; font-size: .72rem; font-weight: 700; color: #8a6d00; background: #fff7dc; border: 1px solid #f0e2a8; padding: 3px 10px; border-radius: 99px; }
</style>
@endsection

@section('content')
<div class="checkout-page">
    <div class="container">

        <h1 style="font-size:1.15rem; font-weight:700; color:#222; margin-bottom:20px;">
            <i class="las la-lock mr-1" style="color:#679941;"></i> Secure Checkout
            <span style="color:#aaa; font-size:.85rem; font-weight:400;">— Order #{{ $order->id }}</span>
        </h1>

        <div class="row">

            {{-- ── PAGO ── --}}
            <div class="col-lg-7 mb-4">
                <div class="checkout-card">
                    <h3>Payment Details</h3>

                    @if(str_starts_with($stripeKey ?? '', 'pk_test_51Te0U6Qg0O05LAp8ySAJB0Fa8QA0ARnv2ZbVXFpyqpvflNcVeM6eBBYMsqnP64t2G2SjjxN9ScOslBrEPDVVvZtn00fMsH9Rih'))
                        <span class="test-badge">Modo prueba — tarjeta 4242 4242 4242 4242, fecha futura, CVC 123</span>
                    @endif

                    <div id="payment-element">
                        <div class="stripe-loading">
                            <div class="spinner"></div> Cargando formulario de pago seguro…
                        </div>
                    </div>

                    <button id="pay-button" class="btn-pay" type="button" disabled>
                        Pay ${{ number_format($total, 2) }}
                    </button>

                    <p id="payment-error" role="alert" aria-live="polite"></p>

                    <div class="secure-note">
                        <i class="las la-shield-alt"></i>
                        Procesado por Stripe — tu tarjeta nunca pasa por nuestros servidores
                    </div>
                </div>
            </div>

            {{-- ── RESUMEN ── --}}
            <div class="col-lg-5">
                <div class="checkout-card">
                    <h3>Order Summary</h3>

                    @foreach($cartItems as $item)
                        <div class="co-item">
                            @if($item->product->thumbnail)
                                <img src="{{ asset('storage/' . $item->product->thumbnail) }}"
                                     alt="{{ $item->product->name }}" class="co-item-img">
                            @else
                                <div class="co-item-img-ph"><i class="las la-image"></i></div>
                            @endif
                            <div>
                                <div class="co-item-name">{{ $item->product->name }}</div>
                                <div class="co-item-meta">
                                    @if($item->variation) {{ $item->variation }} · @endif
                                    × {{ $item->quantity }}
                                </div>
                            </div>
                            <span class="co-item-price">${{ number_format($item->price * $item->quantity, 2) }}</span>
                        </div>
                    @endforeach

                    <div class="summary-row" style="margin-top:14px;">
                        <span>Subtotal</span><span class="val">${{ number_format($total, 2) }}</span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span><span class="val">Free</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span><span class="val">${{ number_format($total, 2) }}</span>
                    </div>

                    <a href="{{ route('cart.index') }}" style="display:block; text-align:center; margin-top:16px; font-size:.82rem; color:#888;">
                        ← Edit cart
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('extra_js')
<script src="https://js.stripe.com/v3/"></script>
<script>
(async function () {
    const CSRF      = document.querySelector('meta[name="csrf-token"]').content;
    const stripeKey = @json($stripeKey);
    const errorBox  = document.getElementById('payment-error');
    const payBtn    = document.getElementById('pay-button');

    if (!stripeKey) {
        // Si esto aparece: falta STRIPE_KEY en .env → php artisan config:clear
        errorBox.textContent = 'Configuración de pagos incompleta. Contacta al administrador.';
        console.error('STRIPE_KEY es null: revisa .env y ejecuta php artisan config:clear');
        document.querySelector('.stripe-loading')?.remove();
        return;
    }

    const stripe = Stripe(stripeKey);

    let elements;
    try {
        const res  = await fetch(@json(route('payments.stripe.intent')), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ order_id: @json($order->id) }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'No se pudo iniciar el pago.');

        elements = stripe.elements({
            clientSecret: data.clientSecret,
            appearance: {
                theme: 'stripe',
                variables: {
                    colorPrimary: '#679941',
                    colorDanger: '#e74c3c',
                    borderRadius: '6px',
                    fontSizeBase: '14px',
                },
            },
        });

        const paymentElement = elements.create('payment');
        paymentElement.mount('#payment-element');
        paymentElement.on('ready', () => { payBtn.disabled = false; });
    } catch (e) {
        errorBox.textContent = e.message;
        document.querySelector('.stripe-loading')?.remove();
        return;
    }

    payBtn.addEventListener('click', async () => {
        payBtn.disabled = true;
        payBtn.textContent = 'Processing…';
        errorBox.textContent = '';

        const { error } = await stripe.confirmPayment({
            elements,
            confirmParams: {
                // Stripe redirige aquí con ?payment_intent=pi_... al aprobar
                return_url: @json(route('checkout.success', $order)),
            },
        });

        // Solo se llega aquí si hubo error (tarjeta rechazada, etc.)
        if (error) {
            errorBox.textContent = error.message;
            payBtn.disabled = false;
            payBtn.textContent = 'Pay ${{ number_format($total, 2) }}';
        }
    });
})();
</script>
@endsection