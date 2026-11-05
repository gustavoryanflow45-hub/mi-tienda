<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Avisos del usuario del token (canal database): cambios de estado de sus
 * pedidos, ventas nuevas, liquidaciones y la decisión sobre su tienda. Son
 * los mismos que la web muestra como banners. Listarlos no los marca como
 * leídos: lo decide la app.
 */
class NotificationController extends Controller
{
    // ── GET /api/v1/notifications ────────────────────────────────
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'unread' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $user = $request->user();

        return NotificationResource::collection(
            ($request->boolean('unread') ? $user->unreadNotifications() : $user->notifications())
                ->paginate($filters['per_page'] ?? 20)
                ->withQueryString()
        )->additional(['meta' => ['unread_count' => $user->unreadNotifications()->count()]]);
    }

    // ── GET /api/v1/notifications/unread-count ───────────────────
    // Para el globito de la app, sin bajar la lista.
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['data' => ['count' => $request->user()->unreadNotifications()->count()]]);
    }

    // ── POST /api/v1/notifications/{id}/read ─────────────────────
    public function markRead(Request $request, string $notification): NotificationResource
    {
        // Solo los avisos propios; el de otro usuario, 404.
        $model = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $model->markAsRead();

        return new NotificationResource($model);
    }

    // ── POST /api/v1/notifications/read-all ──────────────────────
    public function markAllRead(Request $request): Response
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }
}
