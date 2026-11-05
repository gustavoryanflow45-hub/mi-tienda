<?php

namespace App\Services\Payments;

use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Cobro con PayPhone de un pedido pendiente, común al checkout web y a la
 * app (ver PayPhoneService para el flujo prepare → pago → confirm).
 *
 * El clientTransactionId lleva el pedido y el origen: GS{id}-XXXXXXXXXX para
 * la web y GA{id}-XXXXXXXXXX para la app. Así la vuelta de PayPhone, que es
 * la misma URL para los dos, sabe si debe devolver al comprador a la tienda
 * o a la app, sin añadir parámetros a la responseUrl registrada.
 */
class PayPhoneCheckout
{
    public const ORIGIN_WEB = 'S';

    public const ORIGIN_APP = 'A';

    /** Resultados de confirm(). */
    public const PAID = 'paid';

    public const CANCELED = 'canceled';

    public const ERROR = 'error';

    public const MISMATCH = 'mismatch';

    public const UNKNOWN = 'unknown';

    public function __construct(
        private readonly PayPhoneService $payphone,
        private readonly CheckoutService $checkout,
    ) {}

    public function configured(): bool
    {
        return $this->payphone->configured();
    }

    /**
     * Registra el cobro en PayPhone y devuelve la URL de pago: con tarjeta
     * ('card') o con el saldo de la app de PayPhone ('app').
     *
     * @return array{redirect_url: string, client_transaction_id: string}
     *
     * @throws BusinessRuleException si PayPhone no lo acepta
     */
    public function start(Order $order, string $method = 'card', string $origin = self::ORIGIN_WEB): array
    {
        $ivaRate = (float) config('services.payphone.iva_rate', config('app.ec_iva_rate', 0.15));
        // Uno nuevo por intento (PayPhone exige que sea único); lleva el id
        // del pedido para encontrarlo a la vuelta.
        $clientTxId = self::transactionIdFor($order, $origin);

        try {
            $payment = $this->payphone->prepare(PayPhoneService::amounts((float) $order->grand_total, $ivaRate) + [
                'clientTransactionId' => $clientTxId,
                'reference' => __('Pedido :code', ['code' => $order->code]),
                'responseUrl' => route('payments.payphone.return'),
                'cancellationUrl' => route($origin === self::ORIGIN_APP ? 'payments.payphone.app-cancel' : 'payments.payphone.cancel'),
                'lang' => app()->getLocale() === 'en' ? 'en' : 'es',
                'timeZone' => '-5',
            ]);
        } catch (Throwable $e) {
            Log::error('PayPhone prepare failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            throw new BusinessRuleException(__('No se pudo iniciar el pago. Intenta de nuevo.'));
        }

        return [
            'redirect_url' => $method === 'app' ? $payment['payWithPayPhone'] : $payment['payWithCard'],
            'client_transaction_id' => $clientTxId,
        ];
    }

    /**
     * Confirma con PayPhone la transacción $id y, si está aprobada y coincide
     * con el pedido, lo da por pagado. Hay que llamarlo siempre que PayPhone
     * devuelve al comprador: sin Confirm en 5 minutos revierte el cobro.
     *
     * @return array{status: string, order: ?Order, reference: string|int|null}
     */
    public function confirm(int $id, string $clientTxId): array
    {
        $order = self::orderFor($clientTxId);

        if (! $id || ! $order) {
            return ['status' => self::UNKNOWN, 'order' => $order, 'reference' => null];
        }

        if ($order->isPaid()) {
            return ['status' => self::PAID, 'order' => $order, 'reference' => $order->payment_reference];
        }

        try {
            $result = $this->payphone->confirm($id, $clientTxId);
        } catch (Throwable $e) {
            Log::error('PayPhone confirm failed', ['order_id' => $order->id, 'id' => $id, 'error' => $e->getMessage()]);

            return ['status' => self::ERROR, 'order' => $order, 'reference' => null];
        }

        if ((int) ($result['statusCode'] ?? 0) !== PayPhoneService::STATUS_APPROVED) {
            return ['status' => self::CANCELED, 'order' => $order, 'reference' => null];
        }

        // El monto aprobado debe ser el del pedido: si el carrito cambió
        // en otra pestaña después de preparar el pago, no se da por pagado.
        $expected = PayPhoneService::amounts((float) $order->grand_total, 0)['amount'];
        $reference = $result['transactionId'] ?? $id;

        if ((string) ($result['clientTransactionId'] ?? '') !== $clientTxId || (int) ($result['amount'] ?? -1) !== $expected) {
            Log::critical('PayPhone: pago aprobado que no coincide con el pedido', [
                'order_id' => $order->id,
                'expected' => $expected,
                'amount' => $result['amount'] ?? null,
                'transaction_id' => $result['transactionId'] ?? null,
            ]);

            return ['status' => self::MISMATCH, 'order' => $order, 'reference' => $reference];
        }

        $this->checkout->completePayment($order, 'payphone', (string) $reference);

        return ['status' => self::PAID, 'order' => $order, 'reference' => $reference];
    }

    public static function transactionIdFor(Order $order, string $origin = self::ORIGIN_WEB): string
    {
        return 'G'.$origin.$order->id.'-'.Str::upper(Str::random(10));
    }

    /** Pedido codificado en el clientTransactionId (G{S|A}{id}-XXXXXXXXXX). */
    public static function orderFor(string $clientTxId): ?Order
    {
        if (! preg_match('/^G[SA](\d+)-[A-Z0-9]{10}$/', $clientTxId, $m)) {
            return null;
        }

        return Order::find((int) $m[1]);
    }

    /** Si el pago se inició desde la app (vuelve a ella y no a la tienda). */
    public static function fromApp(string $clientTxId): bool
    {
        return str_starts_with($clientTxId, 'G'.self::ORIGIN_APP);
    }
}
