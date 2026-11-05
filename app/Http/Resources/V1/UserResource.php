<?php

namespace App\Http\Resources\V1;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * El usuario tal como lo ve la app. Nunca incluye campos internos
 * (password, verification_code, banned, referral...): solo lo listado.
 *
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $shop = $this->isSeller() ? $this->shop : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar ? uploaded_asset($this->avatar) : null,
            // customer | seller. Una fila vieja con user_type = admin es un
            // comprador más para la app.
            'type' => $this->isSeller() ? 'seller' : 'customer',
            'email_verified' => $this->isVerified(),
            'balance' => round((float) $this->balance, 2),
            'shop' => $shop ? [
                'id' => $shop->id,
                'name' => $shop->name,
                'status' => match ((int) $shop->status) {
                    Shop::APPROVED => 'approved',
                    Shop::REJECTED => 'rejected',
                    default => 'pending',
                },
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
