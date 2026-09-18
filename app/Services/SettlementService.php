<?php

namespace App\Services;

use App\Models\OrderDetail;
use App\Models\SellerSettlement;
use App\Models\User;
use App\Notifications\SellerSettledNotification;
use Illuminate\Support\Facades\DB;

/**
 * Ventas pendientes de liquidar y su cierre.
 *
 * Una venta cuenta cuando su línea está cobrada (order_details.payment_status
 * = 'paid'), entregada (delivery_status = 'delivered') y no pertenece a
 * ninguna liquidación (settlement_id null); ver OrderDetail::scopeSettleable.
 * El panel del vendedor y el panel del admin leen el mismo cálculo, así que
 * la cifra que ve el vendedor es exactamente la que el admin liquida.
 */
class SettlementService
{
    /** Expresión SQL del importe de una línea, la misma del panel. */
    public const LINE_AMOUNT = '(price * quantity) + shipping_cost + tax - discount_on_product';

    public function commissionRate(): float
    {
        return (float) config('app.seller_commission_rate', 0.25);
    }

    /**
     * Totales sin liquidar de un vendedor: ventas brutas, comisión retenida
     * y neto a pagar.
     *
     * @return array{total_sales: float, commission_rate: float, commission: float, net_amount: float, lines_count: int}
     */
    public function pendingFor(int $sellerId): array
    {
        $row = OrderDetail::query()
            ->settleable()
            ->where('seller_id', $sellerId)
            ->selectRaw('COALESCE(SUM('.self::LINE_AMOUNT.'), 0) AS total, COUNT(*) AS lines')
            ->first();

        return $this->breakdown((float) $row->total, (int) $row->lines);
    }

    /**
     * Lo mismo para todos los vendedores a la vez, indexado por seller_id.
     * Solo aparecen los que tienen algo pendiente.
     *
     * @return array<int, array{total_sales: float, commission_rate: float, commission: float, net_amount: float, lines_count: int}>
     */
    public function pendingForAll(): array
    {
        return OrderDetail::query()
            ->settleable()
            ->whereNotNull('seller_id')
            ->groupBy('seller_id')
            ->selectRaw('seller_id, COALESCE(SUM('.self::LINE_AMOUNT.'), 0) AS total, COUNT(*) AS lines')
            ->get()
            ->mapWithKeys(fn ($row) => [
                (int) $row->seller_id => $this->breakdown((float) $row->total, (int) $row->lines),
            ])
            ->all();
    }

    /**
     * Cierra el ciclo: marca las líneas pendientes con la nueva liquidación
     * (el panel del vendedor vuelve a cero) y acredita el neto en su
     * billetera, desde donde puede pedir el retiro. Devuelve null si no
     * había nada que liquidar.
     */
    public function settle(User $seller, User $admin): ?SellerSettlement
    {
        $settlement = DB::transaction(function () use ($seller, $admin) {
            // Se bloquean las líneas concretas (no un agregado, que PostgreSQL
            // no deja bloquear) para que dos admins no liquiden lo mismo.
            $lines = OrderDetail::query()
                ->settleable()
                ->where('seller_id', $seller->id)
                ->lockForUpdate()
                ->get(['id', 'price', 'quantity', 'shipping_cost', 'tax', 'discount_on_product']);

            if ($lines->isEmpty()) {
                return null;
            }

            $total = $lines->sum(fn (OrderDetail $line) => ((float) $line->price * (int) $line->quantity)
                + (float) $line->shipping_cost
                + (float) $line->tax
                - (float) $line->discount_on_product);

            $settlement = SellerSettlement::create([
                'seller_id' => $seller->id,
                'admin_id' => $admin->id,
                'settled_at' => now(),
            ] + $this->breakdown($total, $lines->count()));

            OrderDetail::whereIn('id', $lines->pluck('id'))
                ->update(['settlement_id' => $settlement->id]);

            // El pago es saldo de la billetera: mismo mecanismo que una recarga
            // aprobada (WalletController@approve); el retiro sale de ahí.
            User::whereKey($seller->id)->increment('balance', $settlement->net_amount);

            return $settlement;
        });

        if ($settlement) {
            // Fuera de la transacción: si el correo falla, la liquidación ya quedó.
            $seller->notify(new SellerSettledNotification($settlement));
        }

        return $settlement;
    }

    /**
     * @return array{total_sales: float, commission_rate: float, commission: float, net_amount: float, lines_count: int}
     */
    private function breakdown(float $total, int $lines): array
    {
        $rate = $this->commissionRate();
        $total = round($total, 2);
        $commission = round($total * $rate, 2);

        return [
            'total_sales' => $total,
            'commission_rate' => $rate,
            'commission' => $commission,
            'net_amount' => round($total - $commission, 2),
            'lines_count' => $lines,
        ];
    }
}
