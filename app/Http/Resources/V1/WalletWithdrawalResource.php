<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Solicitud de retiro. La cuenta bancaria va solo con sus últimos 4
 * dígitos: la app no la necesita entera para mostrar el historial, y un
 * token robado no debe servir para leerla.
 *
 * @mixin \App\Models\WalletWithdrawal
 */
class WalletWithdrawalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // Lo descontado del saldo; se transfieren net_amount (amount − fee).
            'amount' => round((float) $this->amount, 2),
            'fee' => round((float) $this->fee, 2),
            'net_amount' => $this->netAmount(),
            'full_name' => $this->full_name,
            'bank_name' => $this->bank_name,
            'account_last4' => substr((string) $this->account_number, -4),
            'status' => in_array($this->status, ['approved', 'rejected'], true) ? $this->status : 'pending',
            // true = lo cubren ventas ya liquidadas (dato para el admin; igual
            // espera su aprobación).
            'from_settlement' => (bool) $this->from_settlement,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
