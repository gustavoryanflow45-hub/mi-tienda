<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OrderResource;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\Payments\PayPhoneCheckout;
use App\Services\Payments\StripeCheckout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cobro de un pedido pendiente desde la app, con la misma lógica que el
 * checkout web (StripeCheckout, PayPhoneCheckout):
 *
 *  - Stripe: la app recibe el client_secret y paga con la PaymentSheet de
 *    flutter_stripe; luego llama a stripe/confirm (el webhook también lo
 *    registra, pero puede tardar o no llegar a un entorno local).
 *  - PayPhone: la app abre redirect_url en un navegador in-app. Al volver,
 *    el servidor confirma (payments.payphone.return) y redirige al esquema
 *    de la app; payphone/confirm sirve si la app cortó antes esa vuelta.
 *
 * Iniciar un cobro vuelve a sincronizar el pedido con el carrito, como cada
 * intento en la web: se cobra lo que hay en el carrito en ese momento.
 */
class PaymentController extends Controller
{
    public function __construct(private CheckoutService $checkout) {}

    // ── POST /api/v1/checkout/orders/{id}/stripe ─────────────────
    public function stripe(Request $request, int $order, StripeCheckout $stripe): JsonResponse
    {
        $order = $this->payable($request, $order);
        $intent = $stripe->intentFor($order);

        return response()->json(['data' => [
            'client_secret' => $intent->client_secret,
            'payment_intent_id' => $intent->id,
            'publishable_key' => config('services.stripe.key'),
            'amount' => StripeCheckout::cents($order),
            'currency' => StripeCheckout::CURRENCY,
            'order' => new OrderResource(CheckoutController::loaded($order)),
        ]]);
    }

    // ── POST /api/v1/checkout/orders/{id}/stripe/confirm ─────────
    // Sin cuerpo. Devuelve el pedido: payment_status dice si quedó pagado.
    public function stripeConfirm(Request $request, int $order, StripeCheckout $stripe): OrderResource
    {
        $order = CheckoutController::ownOrder($request, $order);

        try {
            $stripe->verify($order);
        } catch (Throwable $e) {
            Log::warning('Stripe verify (API) failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            throw new BusinessRuleException(__('No pudimos confirmar el pago todavía. Si se te cobró, se registrará en unos minutos.'));
        }

        return new OrderResource(CheckoutController::loaded($order->refresh()));
    }

    // ── POST /api/v1/checkout/orders/{id}/payphone ───────────────
    public function payphone(Request $request, int $order, PayPhoneCheckout $payphone): JsonResponse
    {
        $data = $request->validate(['method' => ['nullable', 'in:card,app']]);

        if (! $payphone->configured()) {
            Log::error('PayPhone: faltan PAYPHONE_TOKEN o PAYPHONE_STORE_ID');
            abort(503, __('Configuración de pagos incompleta. Contacta al administrador.'));
        }

        $order = $this->payable($request, $order);
        $payment = $payphone->start($order, $data['method'] ?? 'card', PayPhoneCheckout::ORIGIN_APP);

        return response()->json(['data' => $payment + [
            // El navegador in-app termina en esta URL (con ?status=…&order=…).
            'return_url' => config('api.app_payment_return_url'),
            'order' => new OrderResource(CheckoutController::loaded($order)),
        ]]);
    }

    // ── POST /api/v1/checkout/orders/{id}/payphone/confirm ───────
    public function payphoneConfirm(Request $request, int $order, PayPhoneCheckout $payphone): OrderResource
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'client_transaction_id' => ['required', 'string', 'max:50'],
        ]);

        $order = CheckoutController::ownOrder($request, $order);

        // La transacción debe ser de este pedido.
        if (PayPhoneCheckout::orderFor($data['client_transaction_id'])?->id !== $order->id) {
            abort(404);
        }

        $result = $payphone->confirm($data['id'], $data['client_transaction_id']);

        $error = match ($result['status']) {
            PayPhoneCheckout::PAID => null,
            PayPhoneCheckout::CANCELED => __('El pago fue cancelado o rechazado. No se realizó ningún cobro.'),
            PayPhoneCheckout::MISMATCH => __('El monto cobrado no coincide con tu pedido. Contacta a soporte con la referencia :ref.', [
                'ref' => $result['reference'],
            ]),
            default => __('No pudimos confirmar el pago con PayPhone. Si se te cobró, se revertirá automáticamente; vuelve a intentarlo.'),
        };

        if ($error) {
            throw new BusinessRuleException($error);
        }

        return new OrderResource(CheckoutController::loaded($order->refresh()));
    }

    /**
     * Pedido del usuario, sin pagar y al día con el carrito.
     *
     * @throws BusinessRuleException si ya está pagado o el carrito está vacío
     */
    private function payable(Request $request, int $id): Order
    {
        $order = CheckoutController::ownOrder($request, $id);

        if ($order->isPaid()) {
            throw new BusinessRuleException(__('Este pedido ya está pagado.'));
        }

        $synced = $this->checkout->syncPendingOrder(
            $request->user(),
            $order,
            $order->shippingInfo() ?: $this->checkout->shippingFrom($request->user(), []),
        );

        return $synced ?? throw new BusinessRuleException(__('Tu carrito está vacío.'));
    }
}
