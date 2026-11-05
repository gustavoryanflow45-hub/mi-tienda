<?php

namespace App\Services\Push;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Cliente mínimo de la API HTTP v1 de Firebase Cloud Messaging, sin SDK:
 *
 *  1. Con la cuenta de servicio (JSON de Firebase, services.fcm.credentials)
 *     firma un JWT RS256 y lo cambia por un token OAuth2 de Google, que se
 *     guarda en caché hasta 5 minutos antes de caducar.
 *  2. POST https://fcm.googleapis.com/v1/projects/{project}/messages:send,
 *     un mensaje por token de dispositivo.
 *
 * send() devuelve SENT o INVALID_TOKEN (el dispositivo ya no existe o el
 * token es de otro proyecto: hay que olvidarlo) y lanza RuntimeException en
 * los fallos pasajeros (429, 5xx, red) para que el job lo reintente.
 */
class FcmClient
{
    public const SENT = 'sent';

    public const INVALID_TOKEN = 'invalid_token';

    public const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private const CACHE_KEY = 'fcm:access_token';

    private ?array $credentials = null;

    public function configured(): bool
    {
        return (bool) config('services.fcm.enabled', true) && $this->credentials() !== null;
    }

    public function send(string $token, FcmMessage $message): string
    {
        $response = $this->post($token, $message);

        // Token OAuth revocado o caducado antes de tiempo: uno nuevo, una vez.
        if ($response->status() === 401) {
            Cache::forget(self::CACHE_KEY);
            $response = $this->post($token, $message);
        }

        if ($response->successful()) {
            return self::SENT;
        }

        if ($this->isInvalidToken($response)) {
            return self::INVALID_TOKEN;
        }

        Log::warning('FCM: envío fallido', [
            'status' => $response->status(),
            'error' => $response->json('error.status'),
            'token' => self::mask($token),
        ]);

        throw new RuntimeException('FCM respondió '.$response->status().' '.$response->json('error.status'));
    }

    private function post(string $token, FcmMessage $message): Response
    {
        return Http::withToken($this->accessToken())
            ->acceptJson()
            ->asJson()
            ->timeout(10)
            ->post('https://fcm.googleapis.com/v1/projects/'.$this->credentials()['project_id'].'/messages:send', $message->payload($token));
    }

    /**
     * UNREGISTERED: la app se desinstaló o el token caducó. SENDER_ID_MISMATCH:
     * es de otro proyecto de Firebase. INVALID_ARGUMENT solo cuenta si FCM
     * dice que el token no es válido (si no, el problema es el mensaje y no
     * hay que borrar nada).
     */
    private function isInvalidToken(Response $response): bool
    {
        $codes = collect($response->json('error.details', []))->pluck('errorCode')->filter();

        if ($codes->intersect(['UNREGISTERED', 'SENDER_ID_MISMATCH'])->isNotEmpty() || $response->status() === 404) {
            return true;
        }

        return $response->json('error.status') === 'INVALID_ARGUMENT'
            && str_contains(strtolower((string) $response->json('error.message')), 'registration token');
    }

    private function accessToken(): string
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $credentials = $this->credentials() ?? throw new RuntimeException('FCM sin credenciales (services.fcm.credentials).');
        $tokenUri = $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token';

        $response = Http::asForm()->timeout(10)->post($tokenUri, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $this->jwt($credentials, $tokenUri),
        ]);

        if (! $response->successful() || ! $response->json('access_token')) {
            throw new RuntimeException('FCM: Google no entregó un token OAuth ('.$response->status().').');
        }

        $token = (string) $response->json('access_token');
        Cache::put(self::CACHE_KEY, $token, max(60, (int) $response->json('expires_in', 3600) - 300));

        return $token;
    }

    private function jwt(array $credentials, string $audience): string
    {
        $now = time();
        $encode = fn (array $part) => rtrim(strtr(base64_encode(json_encode($part)), '+/', '-_'), '=');

        $unsigned = $encode(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$encode([
            'iss' => $credentials['client_email'],
            'scope' => self::SCOPE,
            'aud' => $audience,
            'iat' => $now,
            'exp' => $now + 3600,
        ]);

        if (! openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('FCM: no se pudo firmar el JWT (¿private_key válida?).');
        }

        return $unsigned.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    }

    /**
     * El JSON de la cuenta de servicio (ruta absoluta o relativa al proyecto).
     * Null si no está o le faltan campos: el canal no envía nada.
     */
    private function credentials(): ?array
    {
        if ($this->credentials !== null) {
            return $this->credentials;
        }

        $path = (string) config('services.fcm.credentials');

        if ($path === '') {
            return null;
        }

        $path = str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) ? $path : base_path($path);
        $json = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

        if (! is_array($json) || empty($json['project_id']) || empty($json['client_email']) || empty($json['private_key'])) {
            Log::warning('FCM: credenciales ausentes o incompletas', ['path' => $path]);

            return null;
        }

        return $this->credentials = $json;
    }

    /** Para los logs: nunca el token entero. */
    public static function mask(string $token): string
    {
        return substr($token, 0, 8).'…'.substr($token, -4);
    }
}
