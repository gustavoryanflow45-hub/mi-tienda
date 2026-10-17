<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Hasta el commit 34a2156 (2026-09-11) el checkout copiaba el envío de cada
 * producto a order_details.shipping_cost pero no lo sumaba al pedido: esos
 * pedidos tienen shipping_total = 0 y se cobraron sin envío.
 *
 * Como la liquidación paga al vendedor precio + envío + impuesto por línea
 * (SettlementService::LINE_AMOUNT), un pedido de esos que llegara a pagarse
 * le liquidaría un envío que el comprador nunca pagó. Se pone a 0 el envío de
 * sus líneas mientras sigan sin pagar.
 *
 * Las tres condiciones juntas solo las cumplen esos pedidos viejos: el
 * checkout actual guarda shipping_total = suma del envío de las líneas. Las
 * líneas ya pagadas o liquidadas no se tocan, para que cada liquidación siga
 * cuadrando con las líneas que pagó.
 */
return new class extends Migration
{
    public function up(): void
    {
        $orderIds = DB::table('orders')
            ->where('payment_status', 'unpaid')
            ->where('shipping_total', 0)
            ->pluck('id');

        // whereIn por tandas: SQLite limita los parámetros de una consulta
        foreach ($orderIds->chunk(500) as $chunk) {
            DB::table('order_details')
                ->whereIn('order_id', $chunk)
                ->where('shipping_cost', '>', 0)
                ->whereNull('settlement_id')
                ->where('payment_status', 'unpaid')
                ->update(['shipping_cost' => 0]);
        }
    }

    public function down(): void
    {
        // El envío que se borra nunca se cobró: no hay nada que restaurar.
    }
};
