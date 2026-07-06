<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Services\Payments\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Throwable;

class StripeController extends Controller
{
    public function __construct(private readonly StripeService $stripe) {}

    public function createIntent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $order = Order::findOrFail($validated['order_id']);

        if ($order->session_id !== session()->getId() &&
            $order->user_id !== $request->user()?->id) {
            abort(403);
        }

        if ($order->isPaid()) {
            return response()->json(['message' => 'El pedido ya está pagado.'], 409);
        }

        try {
            $intent = $this->stripe->createIntent(
                amountInCents: (int) round($order->grand_total * 100),
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

        return response()->json(['clientSecret' => $intent->client_secret]);
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
