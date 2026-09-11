<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\Payments\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Throwable;

class StripeController extends Controller
{
    /** Estados en los que un PaymentIntent todavía admite cambiar el monto y volver a cobrarse. */
    private const REUSABLE_STATUSES = ['requires_payment_method', 'requires_confirmation', 'requires_action'];

    /** Estados en los que el dinero ya está comprometido: nunca crear otro intent. */
    private const SETTLING_STATUSES = ['processing', 'succeeded', 'requires_capture'];

    public function __construct(
        private readonly StripeService $stripe,
        private readonly CheckoutService $checkout,
    ) {}

    /**
     * Se llama al pulsar "Pagar", no al abrir /checkout: el pedido nace aquí,
     * a partir del carrito, para que salir del checkout sin pagar no deje un
     * pedido registrado al comprador ni una venta al vendedor.
     */
    public function createIntent(Request $request): JsonResponse
    {
        $order = $this->checkout->pendingOrderFor($request->user());

        if (! $order) {
            return response()->json(['message' => 'Tu carrito está vacío.'], 422);
        }

        $amountInCents = (int) round($order->grand_total * 100);

        // El pedido ya tiene un intent: reutilízalo en vez de crear otro en cada
        // intento de pago, que dejaba el dashboard lleno de "incomplete".
        if ($order->payment_intent_id) {
            try {
                $intent = $this->stripe->retrieveIntent($order->payment_intent_id);

                // Un pago en curso o ya cobrado no se toca: crear otro intent aquí
                // permitiría cobrarle dos veces al comprador.
                if (in_array($intent->status, self::SETTLING_STATUSES, true)) {
                    return response()->json([
                        'message' => 'Ya hay un pago en curso para este pedido. Espera unos segundos y recarga la página.',
                    ], 409);
                }

                if (in_array($intent->status, self::REUSABLE_STATUSES, true)) {
                    if ($intent->amount !== $amountInCents) {
                        $intent = $this->stripe->updateIntent($intent->id, ['amount' => $amountInCents]);
                    }

                    return $this->intentResponse($order, $intent->client_secret);
                }

                // Cancelado o en cualquier otro estado terminal: cae y crea uno nuevo.
            } catch (Throwable $e) {
                // El id puede no existir en esta cuenta (p. ej. al pasar de test a
                // live). No es fatal: se crea uno nuevo abajo.
                Log::warning('Stripe: no se pudo reutilizar el intent', [
                    'order_id'  => $order->id,
                    'intent_id' => $order->payment_intent_id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        try {
            $intent = $this->stripe->createIntent(
                amountInCents: $amountInCents,
                currency:      'usd',
                metadata:      ['order_id' => (string) $order->id],
            );
        } catch (Throwable $e) {
            Log::error('Stripe createIntent failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            return response()->json(['message' => 'No se pudo iniciar el pago. Intenta de nuevo.'], 422);
        }

        $order->update(['payment_intent_id' => $intent->id]);

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
                $order->markPaid('stripe', $intent->id);

                Cart::query()
                    ->when(
                        $order->user_id,
                        fn($q) => $q->where('user_id', $order->user_id),
                        fn($q) => $q->where('session_id', $order->session_id),
                    )
                    ->delete();
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
