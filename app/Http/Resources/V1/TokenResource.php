<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\NewAccessToken;

/**
 * Respuesta de login y rotación: el token en texto plano (la única vez que
 * existe fuera de la app), su caducidad, el dispositivo y el usuario.
 *
 * @property NewAccessToken $resource
 */
class TokenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $token = $this->resource->accessToken;

        return [
            'token' => $this->resource->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->expires_at?->toIso8601String(),
            'abilities' => $token->abilities,
            'device' => [
                'id' => $token->id,
                'device_id' => $token->device_id,
                'device_name' => $token->name,
            ],
            'user' => new UserResource($token->tokenable),
        ];
    }
}
