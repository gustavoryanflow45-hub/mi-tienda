<?php

namespace App\Http\Controllers\Payments;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Services\CheckoutService;
use App\Services\Payments\PayPhoneCheckout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Pasarela para Ecuador (GeolocationService::gatewayFor). Flujo por
 * redirección: ver PayPhoneService; la lógica del cobro, compartida con la
 * API, en PayPhoneCheckout.
 */
class PayPhoneController extends Controller
{
    public function __construct(
        private readonly PayPhoneCheckout $payphone,
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

        try {
            $payment = $this->payphone->start($order, $data['method'] ?? 'card');
        } catch (BusinessRuleException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['redirectUrl' => $payment['redirect_url']]);
    }

    /**
     * GET /payments/payphone/return?id=…&clientTransactionId=… — PayPhone
     * vuelve aquí tras el pago, se haya iniciado en la web o en la app.
     * Confirmar es obligatorio (sin Confirm en 5 minutos PayPhone revierte el
     * cobro), así que no exige sesión: si se perdió, el pago se registra
     * igual y luego se pide iniciar sesión.
     */
    public function handleReturn(Request $request): RedirectResponse
    {
        $clientTxId = (string) $request->query('clientTransactionId');
        $result = $this->payphone->confirm((int) $request->query('id'), $clientTxId);
        $order = $result['order'];

        // Pago iniciado desde la app: la confirmación ya está hecha; se vuelve
        // a ella (el navegador in-app espera esa URL) con el resultado.
        if (PayPhoneCheckout::fromApp($clientTxId)) {
            return self::backToApp($result['status'], $order?->id);
        }

        $failure = match ($result['status']) {
            PayPhoneCheckout::UNKNOWN => ['error', __('No pudimos identificar el pago. Si se te cobró, contacta a soporte.')],
            PayPhoneCheckout::ERROR => ['error', __('No pudimos confirmar el pago con PayPhone. Si se te cobró, se revertirá automáticamente; vuelve a intentarlo.')],
            PayPhoneCheckout::CANCELED => ['warning', __('El pago fue cancelado o rechazado. No se realizó ningún cobro.')],
            PayPhoneCheckout::MISMATCH => ['error', __('El monto cobrado no coincide con tu pedido. Contacta a soporte con la referencia :ref.', [
                'ref' => $result['reference'],
            ])],
            default => null,
        };

        if ($failure) {
            return redirect()->route('checkout')->with(...$failure);
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
    public function cancel(): RedirectResponse
    {
        return redirect()->route('checkout')->with('warning', __('Cancelaste el pago. Tu carrito sigue intacto.'));
    }

    /** GET /payments/payphone/app/cancel — lo mismo para un pago iniciado en la app. */
    public function appCancel(): RedirectResponse
    {
        return self::backToApp(PayPhoneCheckout::CANCELED);
    }

    /**
     * Redirección al esquema de la app (config api.app_payment_return_url),
     * que el navegador in-app (flutter_web_auth_2 / ASWebAuthenticationSession)
     * intercepta para cerrarse. Lleva solo el resultado y el id del pedido: la
     * app consulta el estado real en GET /api/v1/checkout/orders/{id}.
     */
    private static function backToApp(string $status, ?int $orderId = null): RedirectResponse
    {
        $query = http_build_query(array_filter(['status' => $status, 'order' => $orderId]));

        return redirect()->away(config('api.app_payment_return_url').'?'.$query);
    }
}
