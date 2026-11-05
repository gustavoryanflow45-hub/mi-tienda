<?php

namespace App\Http\Controllers\Payments;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\PaymentInProgressException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\Payments\StripeCheckout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeController extends Controller
{
    public function __construct(
        private readonly StripeCheckout $stripe,
        private readonly CheckoutService $checkout,
    ) {}

    /**
     * Se llama al pulsar "Pagar", no al abrir /checkout: el pedido nace aquí,
     * a partir del carrito, para que salir del checkout sin pagar no deje un
     * pedido registrado al comprador ni una venta al vendedor. El intent se
     * reutiliza entre intentos (StripeCheckout::intentFor).
     */
    public function createIntent(Request $request): JsonResponse
    {
        $order = $this->checkout->pendingOrderFor($request->user());

        if (! $order) {
            return response()->json(['message' => __('Tu carrito está vacío.')], 422);
        }

        try {
            $intent = $this->stripe->intentFor($order);
        } catch (PaymentInProgressException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (BusinessRuleException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->intentResponse($order, $intent->client_secret);
    }

    /** El front necesita el secret y a dónde volver tras aprobar el pago. */
    private function intentResponse(Order $order, ?string $clientSecret): JsonResponse
    {
        return response()->json([
            'clientSecret' => $clientSecret,
            'orderId' => $order->id,
            'returnUrl' => route('checkout.success', $order),
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
                config('services.stripe.webhook_secret'),
            );
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook: firma inválida', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $intent  = $event->data->object;
            $orderId = $intent->metadata->order_id ?? null;

            Log::info('Stripe payment_intent.succeeded', ['order_id' => $orderId, 'pi' => $intent->id]);

            $order = $orderId ? Order::find($orderId) : null;

            if ($order && ! $order->isPaid()) {
                $this->checkout->completePayment($order, 'stripe', $intent->id);
            }
        }

        if ($event->type === 'payment_intent.payment_failed') {
            $orderId = $event->data->object->metadata->order_id ?? null;
            Log::warning('Stripe payment_intent.payment_failed', ['order_id' => $orderId]);

            Order::where('id', $orderId)
                ->where('payment_status', 'unpaid')
                ->update(['status' => 'fallido']);
        }

        return response()->json(['received' => true]);
    }
}
