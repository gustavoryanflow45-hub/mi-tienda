<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CheckoutOrderRequest;
use App\Http\Resources\V1\CartItemResource;
use App\Http\Resources\V1\OrderResource;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\GeolocationService;
use App\Services\Payments\PayPhoneCheckout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Checkout de la app, en dos pasos como el web: GET /checkout muestra el
 * resumen sin crear nada; POST /checkout/orders crea (o pone al día) el
 * pedido pendiente con los datos de envío. El cobro va después, en
 * PaymentController, que vuelve a sincronizar el pedido con el carrito.
 *
 * Un pedido ajeno es siempre 404: no se confirma que exista.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private CheckoutService $checkout,
        private CartService $cart,
    ) {}

    // ── GET /api/v1/checkout ─────────────────────────────────────
    public function show(Request $request, GeolocationService $geo, PayPhoneCheckout $payphone): JsonResponse
    {
        $user = $request->user();
        $items = $this->cart->items($user);

        if ($items->isEmpty()) {
            throw new BusinessRuleException(__('Tu carrito está vacío.'));
        }

        return response()->json(['data' => [
            'items' => CartItemResource::collection($items)->resolve($request),
            'totals' => $this->checkout->totals($items),
            'currency' => 'USD',
            // Del perfil (última dirección guardada); la app lo usa para rellenar el formulario.
            'shipping' => $this->checkout->shippingFrom($user, []),
            'payment' => [
                // Pasarela según el país de la IP, como la web: PayPhone en
                // Ecuador, Stripe en el resto. La app puede ofrecer la otra.
                'suggested_gateway' => $geo->gatewayFor($request),
                'country' => $geo->countryCode($request),
                'gateways' => array_values(array_filter([
                    filled(config('services.stripe.key')) ? 'stripe' : null,
                    $payphone->configured() ? 'payphone' : null,
                ])),
                'stripe_publishable_key' => config('services.stripe.key'),
            ],
        ]]);
    }

    // ── POST /api/v1/checkout/orders ─────────────────────────────
    public function store(CheckoutOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $shipping = $request->shipping();

        $reuse = $request->filled('order_id')
            ? Order::where('user_id', $user->id)->find($request->integer('order_id'))
            : null;

        $order = $this->checkout->syncPendingOrder($user, $reuse, $this->checkout->shippingFrom($user, $shipping));

        if (! $order) {
            throw new BusinessRuleException(__('Tu carrito está vacío.'));
        }

        // Como en la web: la dirección queda para la próxima compra.
        $this->checkout->rememberShipping($user, $shipping);

        return (new OrderResource(self::loaded($order)))
            ->response()
            ->setStatusCode($order->wasRecentlyCreated ? 201 : 200);
    }

    // ── GET /api/v1/checkout/orders/{id} ─────────────────────────
    // Lo que la app consulta tras pagar para saber si el pedido quedó pagado.
    public function order(Request $request, int $order): OrderResource
    {
        return new OrderResource(self::loaded(self::ownOrder($request, $order)));
    }

    /** Pedido del usuario del token; el de otro, 404. */
    public static function ownOrder(Request $request, int $id): Order
    {
        return Order::where('user_id', $request->user()->id)->findOrFail($id);
    }

    public static function loaded(Order $order): Order
    {
        return $order->load('orderDetails.product.category');
    }
}
