<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Solicitud de recarga. `approval` en la base es 0 pendiente, 1 aprobada y
 * -1 rechazada; aquí va como clave estable. El comprobante no se devuelve
 * (solo si lo hay): es un documento del usuario que ya tiene.
 *
 * @mixin \App\Models\WalletRecharge
 */
class WalletRechargeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => round((float) $this->amount, 2),
            // Comisión de red y lo que se acredita al aprobarse (amount − fee).
            'fee' => round((float) $this->fee, 2),
            'net_amount' => $this->netAmount(),
            'network' => $this->network,
            'transaction_id' => $this->transaction_id,
            'has_proof' => filled($this->payment_proof),
            'status' => self::status((int) $this->approval),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    public static function status(int $approval): string
    {
        return match ($approval) {
            1 => 'approved',
            0 => 'pending',
            default => 'rejected',
        };
    }
}
