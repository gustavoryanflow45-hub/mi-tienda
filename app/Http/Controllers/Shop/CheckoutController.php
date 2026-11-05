<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\GeolocationService;
use App\Services\Payments\StripeCheckout;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private GeolocationService $geo,
        private StripeCheckout $stripe,
        private CheckoutService $checkout,
    ) {}

    public function index(Request $request)
    {
        if (! auth()->check()) {
            return redirect()->route('login')->with('error', __('Debes iniciar sesión para continuar.'));
        }

        $user = $request->user();
        $cartItems = $this->checkout->items($user);

        if ($cartItems->isEmpty()) {
            return redirect('/')->with('error', __('Tu carrito está vacío.'));
        }

        // Aquí no se crea ningún pedido: se materializa recién cuando el
        // comprador pulsa "Pagar" (ver CheckoutService::pendingOrderFor).
        $totals = $this->checkout->totals($cartItems);
        $shipping = $this->checkout->shippingSnapshot($user);

        $gateway = in_array($request->query('gateway'), ['stripe', 'payphone'])
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
            return response()->json(['message' => __('Debes iniciar sesión.')], 401);
        }

        $data = $request->validate(CheckoutService::SHIPPING_RULES);

        session([CheckoutService::SHIPPING_SESSION_KEY => $data]);

        // Si ya hubo un intento de pago, el pedido existe: mantenlo al día.
        $this->checkout->reusableOrderFor($request->user())
            ?->update(['shipping_address' => $data]);

        $this->checkout->rememberShipping($request->user(), $data);

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
                $this->stripe->verify($order, $intentId);
            } catch (\Throwable) {
                // Webhook will handle it if intent retrieve fails
            }
        }

        $order->load('orderDetails.product');

        // La etiqueta de la talla de cada línea sale del producto y de su
        // categoría; sin cargarlas el resumen consulta una vez por artículo.
        $order->load('orderDetails.product.category');

        return view('checkout.success', ['order' => $order]);
    }
}
