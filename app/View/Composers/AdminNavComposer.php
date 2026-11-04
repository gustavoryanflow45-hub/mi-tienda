<?php

namespace App\View\Composers;

use App\Http\Middleware\EnsureAdminTwoFactor;
use App\Models\Shop;
use App\Notifications\OrderArrivedWarehouseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Datos de la barra de layouts/admin: quién está dentro, si ya puede usar
 * el panel (pasó el segundo factor) y los contadores de la navegación.
 *
 * Antes el contador de tiendas pendientes vivía en la barra lateral del
 * dashboard de la tienda; ahora solo existe dentro del panel.
 */
class AdminNavComposer
{
    public function compose(View $view): void
    {
        $admin = Auth::guard('admin')->user();

        $ready = $admin !== null && (
            ! config('admin.require_two_factor')
            || (int) session(EnsureAdminTwoFactor::SESSION_KEY) === (int) $admin->id
        );

        $view->with('adminNav', [
            'admin' => $admin,
            'ready' => $ready,
            // Shop::status 0 = pendiente (Admin\ShopController::PENDING).
            'pending_shops' => $ready ? Shop::where('status', 0)->count() : 0,
            'arrivals' => $ready
                ? $admin->unreadNotifications()->where('type', OrderArrivedWarehouseNotification::class)->count()
                : 0,
        ]);
    }
}
