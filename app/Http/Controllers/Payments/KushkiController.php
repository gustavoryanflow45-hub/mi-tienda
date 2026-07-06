<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KushkiController extends Controller
{
    private function baseUrl(): string
    {
        return config('services.kushki.env') === 'production'
            ? 'https://api.kushkipagos.com'
            : 'https://api-uat.kushkipagos.com';
    }

    public function charge(Request $request)
    {
        $request->validate([
            'token'           => 'required|string',
            'document_type'   => 'required|in:CC,RUC,PP',
            'document_number' => 'required|string',
            'email'           => 'required|email',
            'first_name'      => 'required|string',
            'last_name'       => 'required|string',
        ]);

        $orderId = session('checkout_order_id');
        $order   = $orderId ? Order::find($orderId) : null;

        if (! $order || $order->isPaid()) {
            return response()->json(['status' => 'error', 'message' => 'Pedido inválido o ya pagado.'], 422);
        }

        $total   = (float) $order->grand_total;
        $ivaRate = config('services.kushki.iva_rate', config('app.ec_iva_rate', 0.15));
        $base    = round($total / (1 + $ivaRate), 2);
        $iva     = round($total - $base, 2);

        $response = Http::withHeaders([
            'Private-Merchant-Id' => config('services.kushki.private_id'),
            'Content-Type'        => 'application/json',
        ])->post($this->baseUrl() . '/card/v1/charges', [
            'token'  => $request->token,
            'amount' => [
                'subtotalIva'  => $base,
                'subtotalIva0' => 0,
                'ice'          => 0,
                'iva'          => $iva,
                'currency'     => 'USD',
            ],
            'metadata'       => ['order_id' => (string) $order->id],
            'contactDetails' => [
                'documentType'   => $request->document_type,
                'documentNumber' => $request->document_number,
                'email'          => $request->email,
                'firstName'      => $request->first_name,
                'lastName'       => $request->last_name,
            ],
        ]);

        if ($response->successful()) {
            $body = $response->json();

            $order->markPaid('kushki', $body['ticketNumber'] ?? '');

            Cart::where('user_id', $order->user_id)->delete();

            return response()->json(['status' => 'success', 'ticket' => $body['ticketNumber'] ?? null]);
        }

        Log::error('Kushki charge failed', ['order_id' => $order->id, 'response' => $response->json()]);

        return response()->json([
            'status'  => 'error',
            'message' => $response->json('message') ?? 'El pago fue rechazado',
        ], 422);
    }

    public function webhook(Request $request)
    {
        $ticket  = $request->input('ticketNumber');
        $orderId = $request->input('metadata.order_id');

        if ($orderId) {
            Order::where('id', $orderId)
                ->where('payment_status', 'unpaid')
                ->update([
                    'status'            => 'pagado',
                    'payment_status'    => 'paid',
                    'payment_gateway'   => 'kushki',
                    'payment_reference' => $ticket,
                    'paid_at'           => now(),
                ]);
        }

        return response('ok', 200);
    }
}
