<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Botón de pagos de PayPhone (https://docs.payphone.app/boton-de-pago-por-redireccion).
 *
 *  1. prepare(): el servidor registra el cobro y PayPhone devuelve dos URLs
 *     de pago (tarjeta o saldo de la app). El comprador paga allí: los datos
 *     de la tarjeta nunca pasan por la tienda.
 *  2. PayPhone vuelve a responseUrl con ?id=…&clientTransactionId=…
 *  3. confirm(): el servidor pide el estado real. Si no se confirma en 5
 *     minutos, PayPhone REVIERTE el cobro, así que la vuelta nunca debe fallar
 *     antes de llegar aquí.
 *
 * No hay URL de pruebas aparte: el modo prueba lo decide la aplicación
 * configurada en PayPhone Developer a la que pertenece el token.
 */
class PayPhoneService
{
    public const BASE_URL = 'https://pay.payphonetodoesposible.com';

    /** statusCode de Confirm. */
    public const STATUS_CANCELED = 2;

    public const STATUS_APPROVED = 3;

    public function __construct(
        private readonly ?string $token,
        private readonly ?string $storeId,
    ) {}

    public function configured(): bool
    {
        return filled($this->token) && filled($this->storeId);
    }

    /**
     * Desglose que exige PayPhone, en centavos: amount = amountWithoutTax +
     * amountWithTax + tax. El total del pedido ya incluye el IVA, así que se
     * despeja la base con la tasa de app.ec_iva_rate (o services.payphone.iva_rate).
     */
    public static function amounts(float $total, float $ivaRate): array
    {
        $amount = (int) round($total * 100);
        $base = (int) round($amount / (1 + $ivaRate));

        return [
            'amount' => $amount,
            'amountWithoutTax' => 0,
            'amountWithTax' => $base,
            'tax' => $amount - $base,
        ];
    }

    /** @return array{paymentId: string, payWithCard: string, payWithPayPhone: string} */
    public function prepare(array $payload): array
    {
        $response = $this->client()->post('/api/button/Prepare', $payload + [
            'storeId' => $this->storeId,
            'currency' => 'USD',
        ]);

        if (! $response->successful() || ! $response->json('paymentId')) {
            throw new RuntimeException('PayPhone Prepare: '.($response->json('message') ?? $response->status()));
        }

        return $response->json();
    }

    /** Estado definitivo de la transacción (statusCode, amount, transactionId…). */
    public function confirm(int $id, string $clientTransactionId): array
    {
        $response = $this->client()->post('/api/button/V2/Confirm', [
            'id' => $id,
            'clientTxId' => $clientTransactionId,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('PayPhone Confirm: '.($response->json('message') ?? $response->status()));
        }

        return $response->json();
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->withToken((string) $this->token)
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->retry(2, 500, throw: false);
    }
}
