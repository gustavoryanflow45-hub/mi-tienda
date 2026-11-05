<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DeviceResource;
use App\Services\ApiTokenService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Sesiones abiertas de la cuenta, una por dispositivo, y su revocación
 * ("cerrar sesión en el móvil que perdí"). También el registro del token
 * push (FCM) del dispositivo, que vive en su sesión: el envío aún no existe.
 */
class DeviceController extends Controller
{
    // ── GET /api/v1/auth/devices ─────────────────────────────────
    public function index(Request $request): AnonymousResourceCollection
    {
        $devices = $request->user()->tokens()
            ->orderByRaw('COALESCE(last_used_at, created_at) desc')
            ->get();

        return DeviceResource::collection($devices);
    }

    // ── DELETE /api/v1/auth/devices/{id} ─────────────────────────
    public function destroy(Request $request, int $id): Response
    {
        // Solo los tokens propios: un id ajeno es un 404, no un 403.
        $request->user()->tokens()->whereKey($id)->firstOrFail()->delete();

        return response()->noContent();
    }

    // ── DELETE /api/v1/auth/devices ──────────────────────────────
    // Cierra todas las demás sesiones; con ?include_current=1 también esta.
    public function destroyAll(Request $request): Response
    {
        $query = $request->user()->tokens();

        if (! $request->boolean('include_current')) {
            $query->whereKeyNot($request->user()->currentAccessToken()->id);
        }

        $query->delete();

        return response()->noContent();
    }

    // ── PUT /api/v1/auth/push-token ──────────────────────────────
    // La app lo llama tras el login y cada vez que FCM le rota el token.
    public function registerPushToken(Request $request, ApiTokenService $tokens): DeviceResource
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'min:20', 'max:255'],
            'platform' => ['required', 'in:android,ios'],
        ]);

        $current = $request->user()->currentAccessToken();
        $tokens->registerPushToken($current, $data['token'], $data['platform']);

        return new DeviceResource($current->refresh());
    }

    // ── DELETE /api/v1/auth/push-token ───────────────────────────
    // El usuario desactivó los avisos en la app; la sesión sigue abierta.
    public function unregisterPushToken(Request $request): Response
    {
        $request->user()->currentAccessToken()
            ->forceFill(['push_token' => null, 'push_platform' => null, 'push_token_updated_at' => null])
            ->save();

        return response()->noContent();
    }
}
