<?php

namespace App\Services\Payments;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\PaymentInProgressException;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Throwable;

/**
 * Cobro con Stripe de un pedido pendiente, común al checkout web (Stripe
 * Elements) y a la app (PaymentSheet de flutter_stripe): los dos necesitan
 * el client_secret de un PaymentIntent y confirmar el pago al volver.
 */
class StripeCheckout
{
    /** Estados en los que un PaymentIntent todavía admite cambiar el monto y volver a cobrarse. */
    private const REUSABLE_STATUSES = ['requires_payment_method', 'requires_confirmation', 'requires_action'];

    /** Estados en los que el dinero ya está comprometido: nunca crear otro intent. */
    private const SETTLING_STATUSES = ['processing', 'succeeded', 'requires_capture'];

    public const CURRENCY = 'usd';

    public function __construct(
        private readonly StripeService $stripe,
        private readonly CheckoutService $checkout,
    ) {}

    /**
     * PaymentIntent por el total actual del pedido. Reutiliza el que ya tiene
     * en vez de crear otro en cada intento (dejaba el dashboard lleno de
     * "incomplete"), ajustando el monto si el carrito cambió.
     *
     * @throws PaymentInProgressException si ya hay un cobro en curso o hecho
     * @throws BusinessRuleException si Stripe no responde
     */
    public function intentFor(Order $order): PaymentIntent
    {
        $amountInCents = self::cents($order);

        if ($order->payment_intent_id) {
            $inProgress = false;

            try {
                $intent = $this->stripe->retrieveIntent($order->payment_intent_id);

                // Un pago en curso o ya cobrado no se toca: crear otro intent aquí
                // permitiría cobrarle dos veces al comprador.
                $inProgress = in_array($intent->status, self::SETTLING_STATUSES, true);

                if (in_array($intent->status, self::REUSABLE_STATUSES, true)) {
                    return $intent->amount !== $amountInCents
                        ? $this->stripe->updateIntent($intent->id, ['amount' => $amountInCents])
                        : $intent;
                }

                // Cancelado o en cualquier otro estado terminal: cae y crea uno nuevo.
            } catch (Throwable $e) {
                // El id puede no existir en esta cuenta (p. ej. al pasar de test a
                // live). No es fatal: se crea uno nuevo abajo.
                Log::warning('Stripe: no se pudo reutilizar el intent', [
                    'order_id' => $order->id,
                    'intent_id' => $order->payment_intent_id,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($inProgress) {
                throw new PaymentInProgressException(__('Ya hay un pago en curso para este pedido. Espera unos segundos y recarga la página.'));
            }
        }

        try {
            $intent = $this->stripe->createIntent(
                amountInCents: $amountInCents,
                currency: self::CURRENCY,
                metadata: ['order_id' => (string) $order->id],
            );
        } catch (Throwable $e) {
            $this->failed($order, $e);
        }

        $order->update(['payment_intent_id' => $intent->id]);

        return $intent;
    }

    /**
     * Consulta el intent en Stripe y, si está cobrado y es de este pedido,
     * da el pedido por pagado. Es el camino que no depende del webhook (que
     * nunca llega a localhost). Devuelve si el pedido quedó pagado.
     */
    public function verify(Order $order, ?string $intentId = null): bool
    {
        $intentId ??= $order->payment_intent_id;

        if ($order->isPaid() || ! $intentId) {
            return $order->isPaid();
        }

        $intent = $this->stripe->retrieveIntent($intentId);

        if ($intent->status === 'succeeded' && (string) ($intent->metadata['order_id'] ?? '') === (string) $order->id) {
            $this->checkout->completePayment($order, 'stripe', $intent->id);
        }

        return $order->isPaid();
    }

    public static function cents(Order $order): int
    {
        return (int) round($order->grand_total * 100);
    }

    private function failed(Order $order, Throwable $e): never
    {
        Log::error('Stripe createIntent failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);

        throw new BusinessRuleException(__('No se pudo iniciar el pago. Intenta de nuevo.'));
    }
}
