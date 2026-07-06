<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Services\GeolocationService;
use App\Services\Payments\StripeService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private GeolocationService $geo,
        private StripeService $stripe,
    ) {}

    public function index(Request $request)
    {
        if (! auth()->check()) {
            return redirect()->route('login')->with('error', 'Debes iniciar sesión para continuar.');
        }

        $cartItems = Cart::where('user_id', auth()->id())->with('product')->get();
        $total     = $cartItems->sum(fn($i) => $i->price * $i->quantity);

        if ($cartItems->isEmpty()) {
            return redirect('/')->with('error', 'Tu carrito está vacío.');
        }

        $orderId = session('checkout_order_id');
        $order   = $orderId ? Order::find($orderId) : null;

        if (! $order || $order->isPaid()) {
            $order = Order::create([
                'user_id'         => auth()->id(),
                'session_id'      => session()->getId(),
                'code'            => 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
                'status'          => 'pendiente',
                'payment_status'  => 'unpaid',
                'delivery_status' => 'pending',
                'subtotal'        => $total,
                'grand_total'     => $total,
            ]);

            foreach ($cartItems as $item) {
                $order->orderDetails()->create([
                    'seller_id'           => $item->product->added_by ?? null,
                    'product_id'          => $item->product_id,
                    'variation'           => $item->variation,
                    'product_name'        => $item->product->name ?? 'Producto',
                    'price'               => $item->price,
                    'quantity'            => $item->quantity,
                    'tax'                 => $item->tax ?? 0,
                    'shipping_cost'       => $item->shipping_cost ?? 0,
                    'discount_on_product' => 0,
                    'delivery_status'     => 'pending',
                    'payment_status'      => 'unpaid',
                ]);
            }

            session(['checkout_order_id' => $order->id]);
        } elseif ((float) $order->grand_total !== (float) $total) {
            $order->update(['subtotal' => $total, 'grand_total' => $total]);
        }

        $gateway = in_array($request->query('gateway'), ['stripe', 'kushki'])
            ? $request->query('gateway')
            : $this->geo->gatewayFor($request);

        return view('checkout.index', [
            'order'           => $order,
            'cartItems'       => $cartItems,
            'total'           => $total,
            'gateway'         => $gateway,
            'stripeKey'       => config('services.stripe.key'),
            'kushkiPublicId'  => config('services.kushki.public_id'),
            'kushkiInTest'    => config('services.kushki.env') !== 'production',
            'detectedCountry' => $this->geo->countryCode($request),
        ]);
    }

    public function success(Request $request, Order $order)
    {
        if ($order->user_id && $order->user_id !== $request->user()?->id) {
            abort(403);
        }

        if ((int) session('checkout_order_id') === $order->id) {
            session()->forget('checkout_order_id');
        }

        // Stripe redirects here with ?payment_intent=pi_xxx after the user pays.
        // The webhook may not fire in development, so we verify the intent directly.
        $intentId = $request->query('payment_intent');
        if ($intentId && ! $order->isPaid()) {
            try {
                $intent = $this->stripe->retrieveIntent($intentId);
                if ($intent->status === 'succeeded' && (string) ($intent->metadata['order_id'] ?? '') === (string) $order->id) {
                    $order->markPaid('stripe', $intent->id);

                    Cart::query()
                        ->when(
                            $order->user_id,
                            fn($q) => $q->where('user_id', $order->user_id),
                            fn($q) => $q->where('session_id', $order->session_id),
                        )
                        ->delete();
                }
            } catch (\Throwable) {
                // Webhook will handle it if intent retrieve fails
            }
        }

        $order->load('orderDetails.product');

        return view('checkout.success', ['order' => $order]);
    }
}
