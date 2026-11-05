<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DeviceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Sesiones abiertas de la cuenta, una por dispositivo, y su revocación
 * ("cerrar sesión en el móvil que perdí").
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
}
