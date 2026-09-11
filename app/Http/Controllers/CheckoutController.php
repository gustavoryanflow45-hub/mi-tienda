<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\GeolocationService;
use App\Services\Payments\StripeService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private GeolocationService $geo,
        private StripeService $stripe,
        private CheckoutService $checkout,
    ) {}

    public function index(Request $request)
    {
        if (! auth()->check()) {
            return redirect()->route('login')->with('error', 'Debes iniciar sesión para continuar.');
        }

        $user = $request->user();
        $cartItems = $this->checkout->items($user);

        if ($cartItems->isEmpty()) {
            return redirect('/')->with('error', 'Tu carrito está vacío.');
        }

        // Aquí no se crea ningún pedido: se materializa recién cuando el
        // comprador pulsa "Pagar" (ver CheckoutService::pendingOrderFor).
        $totals = $this->checkout->totals($cartItems);
        $shipping = $this->checkout->shippingSnapshot($user);

        $gateway = in_array($request->query('gateway'), ['stripe', 'kushki'])
            ? $request->query('gateway')
            : $this->geo->gatewayFor($request);

        return view('checkout.index', [
            'shipping' => $shipping,
            'cartItems' => $cartItems,
            'subtotal' => $totals['subtotal'],
            'shippingTotal' => $totals['shipping'],
            'taxTotal' => $totals['tax'],
            'total' => $totals['grand_total'],
            'gateway' => $gateway,
            'stripeKey' => config('services.stripe.key'),
            'kushkiPublicId' => config('services.kushki.public_id'),
            'kushkiInTest' => config('services.kushki.env') !== 'production',
            'detectedCountry' => $this->geo->countryCode($request),
        ]);
    }

    /**
     * Guarda los datos de envío del comprador antes de pagar.
     * Todavía no hay pedido que actualizar en la primera pasada, así que
     * quedan en sesión (los recoge CheckoutService al crearlo) y en su
     * dirección por defecto para futuras compras.
     */
    public function saveShipping(Request $request)
    {
        if (! auth()->check()) {
            return response()->json(['message' => 'Debes iniciar sesión.'], 401);
        }

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        session([CheckoutService::SHIPPING_SESSION_KEY => $data]);

        // Si ya hubo un intento de pago, el pedido existe: mantenlo al día.
        $this->checkout->reusableOrderFor($request->user())
            ?->update(['shipping_address' => $data]);

        Address::updateOrCreate(
            ['user_id' => auth()->id(), 'is_default' => 1],
            $data,
        );

        return response()->json(['status' => 'ok']);
    }

    public function success(Request $request, Order $order)
    {
        if ($order->user_id && $order->user_id !== $request->user()?->id) {
            abort(403);
        }

        if ((int) session(CheckoutService::ORDER_SESSION_KEY) === $order->id) {
            $this->checkout->forgetSession();
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
                            fn ($q) => $q->where('user_id', $order->user_id),
                            fn ($q) => $q->where('session_id', $order->session_id),
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
