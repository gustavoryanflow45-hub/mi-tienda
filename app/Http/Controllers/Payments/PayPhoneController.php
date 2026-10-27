<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\Payments\PayPhoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pasarela para Ecuador (GeolocationService::gatewayFor). Flujo por
 * redirección: ver PayPhoneService.
 */
class PayPhoneController extends Controller
{
    public function __construct(
        private readonly PayPhoneService $payphone,
        private readonly CheckoutService $checkout,
    ) {}

    /**
     * POST /payments/payphone/prepare — al pulsar "Pagar", como el intent de
     * Stripe: el pedido nace aquí, a partir del carrito. Devuelve la URL de
     * PayPhone a la que el navegador debe ir.
     */
    public function prepare(Request $request): JsonResponse
    {
        $data = $request->validate(['method' => 'nullable|in:card,app']);

        if (! $this->payphone->configured()) {
            Log::error('PayPhone: faltan PAYPHONE_TOKEN o PAYPHONE_STORE_ID');

            return response()->json(['message' => __('Configuración de pagos incompleta. Contacta al administrador.')], 503);
        }

        $order = $this->checkout->pendingOrderFor($request->user());

        if (! $order) {
            return response()->json(['message' => __('Tu carrito está vacío.')], 422);
        }

        $ivaRate = (float) config('services.payphone.iva_rate', config('app.ec_iva_rate', 0.15));

        try {
            $payment = $this->payphone->prepare(PayPhoneService::amounts((float) $order->grand_total, $ivaRate) + [
                // Uno nuevo por intento (PayPhone exige que sea único); lleva el
                // id del pedido para encontrarlo a la vuelta.
                'clientTransactionId' => self::transactionIdFor($order),
                'reference' => __('Pedido :code', ['code' => $order->code]),
                'responseUrl' => route('payments.payphone.return'),
                'cancellationUrl' => route('payments.payphone.cancel'),
                'lang' => app()->getLocale() === 'en' ? 'en' : 'es',
                'timeZone' => '-5',
            ]);
        } catch (Throwable $e) {
            Log::error('PayPhone prepare failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => __('No se pudo iniciar el pago. Intenta de nuevo.')], 422);
        }

        return response()->json([
            'redirectUrl' => ($data['method'] ?? 'card') === 'app'
                ? $payment['payWithPayPhone']
                : $payment['payWithCard'],
        ]);
    }

    /**
     * GET /payments/payphone/return?id=…&clientTransactionId=… — PayPhone
     * vuelve aquí tras el pago. Confirmar es obligatorio (sin Confirm en 5
     * minutos PayPhone revierte el cobro), así que no exige sesión: si se
     * perdió, el pago se registra igual y luego se pide iniciar sesión.
     */
    public function handleReturn(Request $request)
    {
        $id = (int) $request->query('id');
        $clientTxId = (string) $request->query('clientTransactionId');
        $order = self::orderFor($clientTxId);

        if (! $id || ! $order) {
            return redirect()->route('checkout')->with('error', __('No pudimos identificar el pago. Si se te cobró, contacta a soporte.'));
        }

        if (! $order->isPaid()) {
            try {
                $result = $this->payphone->confirm($id, $clientTxId);
            } catch (Throwable $e) {
                Log::error('PayPhone confirm failed', ['order_id' => $order->id, 'id' => $id, 'error' => $e->getMessage()]);

                return redirect()->route('checkout')->with('error', __('No pudimos confirmar el pago con PayPhone. Si se te cobró, se revertirá automáticamente; vuelve a intentarlo.'));
            }

            if ((int) ($result['statusCode'] ?? 0) !== PayPhoneService::STATUS_APPROVED) {
                return redirect()->route('checkout')->with('warning', __('El pago fue cancelado o rechazado. No se realizó ningún cobro.'));
            }

            // El monto aprobado debe ser el del pedido: si el carrito cambió
            // en otra pestaña después de preparar el pago, no se da por pagado.
            $expected = PayPhoneService::amounts((float) $order->grand_total, 0)['amount'];
            if ((string) ($result['clientTransactionId'] ?? '') !== $clientTxId || (int) ($result['amount'] ?? -1) !== $expected) {
                Log::critical('PayPhone: pago aprobado que no coincide con el pedido', [
                    'order_id' => $order->id,
                    'expected' => $expected,
                    'amount' => $result['amount'] ?? null,
                    'transaction_id' => $result['transactionId'] ?? null,
                ]);

                return redirect()->route('checkout')->with('error', __('El monto cobrado no coincide con tu pedido. Contacta a soporte con la referencia :ref.', [
                    'ref' => $result['transactionId'] ?? $id,
                ]));
            }

            $order->markPaid('payphone', (string) ($result['transactionId'] ?? $id));
            Cart::where('user_id', $order->user_id)->delete();
        }

        // Sesión perdida: el pago ya quedó registrado; el pedido es privado.
        if (! $request->user()) {
            $request->session()->put('url.intended', route('orders.show', $order->id));

            return redirect()->route('login')->with('success', __('Pago confirmado. Inicia sesión para ver tu pedido.'));
        }

        if ($request->user()->id !== $order->user_id) {
            return redirect()->route('home')->with('success', __('Pago confirmado.'));
        }

        return redirect()->route('checkout.success', $order);
    }

    /** GET /payments/payphone/cancel — el comprador abandonó el formulario. */
    public function cancel()
    {
        return redirect()->route('checkout')->with('warning', __('Cancelaste el pago. Tu carrito sigue intacto.'));
    }

    public static function transactionIdFor(Order $order): string
    {
        return 'GS'.$order->id.'-'.Str::upper(Str::random(10));
    }

    /** Pedido codificado en el clientTransactionId (GS{id}-XXXXXXXXXX). */
    public static function orderFor(string $clientTxId): ?Order
    {
        if (! preg_match('/^GS(\d+)-[A-Z0-9]{10}$/', $clientTxId, $m)) {
            return null;
        }

        return Order::find((int) $m[1]);
    }
}
