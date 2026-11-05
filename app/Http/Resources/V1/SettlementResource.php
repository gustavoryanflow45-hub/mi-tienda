<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Liquidación de ventas acreditada al vendedor (SettlementService::settle):
 * el neto ya está sumado a su saldo y se retira sin segunda aprobación.
 *
 * @mixin \App\Models\SellerSettlement
 */
class SettlementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'total_sales' => round((float) $this->total_sales, 2),
            'commission_rate' => round((float) $this->commission_rate, 4),
            'commission' => round((float) $this->commission, 2),
            'net_amount' => round((float) $this->net_amount, 2),
            'lines' => (int) $this->lines_count,
            'settled_at' => $this->settled_at?->toIso8601String(),
        ];
    }
}
