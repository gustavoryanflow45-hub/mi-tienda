<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Una fila de GET /wallet/movements (recarga, retiro o liquidación; ver
 * WalletController::movements()). `amount` va con signo: lo que suma (+) o
 * resta (−) al saldo — en una recarga, ya sin la comisión de red. `id` es el de su tabla: con `type` lleva al detalle
 * (/wallet/recharges/{id}, /wallet/withdrawals/{id}).
 *
 * @property-read object{type: string, id: int, amount: string, status: string, occurred_at: ?string} $resource
 */
class WalletMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $amount = (float) $this->resource->amount;

        return [
            'type' => $this->resource->type,
            'id' => (int) $this->resource->id,
            'amount' => round($this->resource->type === 'withdrawal' ? -$amount : $amount, 2),
            'status' => $this->resource->status,
            'occurred_at' => $this->resource->occurred_at ? Carbon::parse($this->resource->occurred_at)->toIso8601String() : null,
        ];
    }
}
