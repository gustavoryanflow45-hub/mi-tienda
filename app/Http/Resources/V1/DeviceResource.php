<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una sesión abierta (un token de Sanctum) en la lista de dispositivos.
 * Nunca incluye el token ni su hash.
 *
 * @mixin \Laravel\Sanctum\PersonalAccessToken
 */
class DeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->device_id,
            'device_name' => $this->name,
            'abilities' => $this->abilities,
            'current' => (int) $request->user()?->currentAccessToken()?->id === (int) $this->id,
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
        ];
    }
}
