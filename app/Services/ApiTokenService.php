<?php

namespace App\Services;

use App\Models\User;
use App\Support\ApiAbilities;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Tokens de la API, uno por dispositivo.
 *
 * - Cada token caduca (config('api.token_ttl_days')) y lleva el device_id
 *   que manda la app: entrar de nuevo desde el mismo dispositivo reemplaza
 *   su token en vez de acumular sesiones.
 * - rotate() cambia el token en uso por uno nuevo con la caducidad
 *   renovada y las habilidades recalculadas; el viejo deja de valer en el
 *   acto.
 * - Como mucho config('api.max_devices') sesiones por cuenta: al pasarse se
 *   cierra la que menos se ha usado.
 *
 * El texto plano del token solo existe en la respuesta que lo entrega; en
 * la base queda su hash (Sanctum).
 */
class ApiTokenService
{
    public function issue(User $user, string $deviceId, string $deviceName): NewAccessToken
    {
        return DB::transaction(function () use ($user, $deviceId, $deviceName) {
            // Mismo dispositivo: su sesión anterior se sustituye.
            $user->tokens()->where('device_id', $deviceId)->delete();

            $this->enforceDeviceLimit($user);

            $token = $user->createToken(
                $deviceName,
                ApiAbilities::for($user),
                now()->addDays(max(1, (int) config('api.token_ttl_days'))),
            );

            $token->accessToken->forceFill(['device_id' => $deviceId])->save();

            return $token;
        });
    }

    /** Nuevo token para el mismo dispositivo; el actual queda revocado. */
    public function rotate(User $user, PersonalAccessToken $current): NewAccessToken
    {
        return DB::transaction(function () use ($user, $current) {
            $deviceId = $current->device_id ?? 'legacy-'.$current->id;
            // El token push es del dispositivo, no de la sesión: pasa al token nuevo.
            $push = $current->only(['push_token', 'push_platform', 'push_token_updated_at']);
            $current->delete();

            $token = $this->issue($user, $deviceId, $current->name);
            $token->accessToken->forceFill($push)->save();

            return $token;
        });
    }

    /**
     * Registra el token FCM del dispositivo en su sesión. Un mismo token no
     * puede quedar en dos sesiones (otra cuenta que se abrió antes en ese
     * móvil): se quita de las demás, de cualquier usuario.
     */
    public function registerPushToken(PersonalAccessToken $current, string $pushToken, string $platform): void
    {
        DB::transaction(function () use ($current, $pushToken, $platform) {
            PersonalAccessToken::where('push_token', $pushToken)
                ->whereKeyNot($current->getKey())
                ->update(['push_token' => null, 'push_platform' => null, 'push_token_updated_at' => null]);

            $current->forceFill([
                'push_token' => $pushToken,
                'push_platform' => $platform,
                'push_token_updated_at' => now(),
            ])->save();
        });
    }

    /** Deja sitio para un dispositivo más cerrando las sesiones menos usadas. */
    protected function enforceDeviceLimit(User $user): void
    {
        $max = max(1, (int) config('api.max_devices'));
        $excess = $user->tokens()->count() - ($max - 1);

        if ($excess <= 0) {
            return;
        }

        $user->tokens()
            ->orderByRaw('COALESCE(last_used_at, created_at) asc')
            ->orderBy('id')
            ->limit($excess)
            ->pluck('id')
            ->each(fn ($id) => PersonalAccessToken::whereKey($id)->delete());
    }
}
