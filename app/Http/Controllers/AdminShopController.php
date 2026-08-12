<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Notifications\ShopStatusUpdatedNotification;
use Illuminate\Http\Request;

class AdminShopController extends Controller
{
    /** Estados de shops.status (ver Shop::statusLabel). */
    public const PENDING = 0;

    public const APPROVED = 1;

    public const REJECTED = 2;

    /**
     * Las rutas ya llevan 'auth' + 'admin'; esto es defensa en profundidad,
     * igual que en WarehouseController.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (! $request->user()?->isAdmin()) {
                abort(403, 'No autorizado.');
            }

            return $next($request);
        });
    }

    // ── GET /admin/shops ─────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Shop::with('user')->latest();

        if ($request->filled('status') && is_numeric($request->status)) {
            $query->where('status', (int) $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn ($qq) => $qq->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%'));
            });
        }

        $shops = $query->paginate(15)->withQueryString();

        $counts = [
            'total' => Shop::count(),
            'pending' => Shop::where('status', self::PENDING)->count(),
            'approved' => Shop::where('status', self::APPROVED)->count(),
            'rejected' => Shop::where('status', self::REJECTED)->count(),
        ];

        return view('admin.shops', compact('shops', 'counts'));
    }

    // ── POST /admin/shops/{id}/approve ───────────────────────────
    public function approve(int $id)
    {
        $shop = Shop::with('user')->findOrFail($id);

        if ((int) $shop->status === self::APPROVED) {
            return back()->with('warning', "La tienda «{$shop->name}» ya estaba aprobada.");
        }

        $shop->update(['status' => self::APPROVED]);

        $shop->user?->notify(new ShopStatusUpdatedNotification($shop, self::APPROVED));

        return back()->with('success', "Tienda «{$shop->name}» aprobada. Ya puede publicar productos. Se notificó al vendedor.");
    }

    // ── POST /admin/shops/{id}/reject ────────────────────────────
    public function reject(int $id)
    {
        $shop = Shop::with('user')->findOrFail($id);

        if ((int) $shop->status === self::REJECTED) {
            return back()->with('warning', "La tienda «{$shop->name}» ya estaba rechazada.");
        }

        $shop->update(['status' => self::REJECTED]);

        $shop->user?->notify(new ShopStatusUpdatedNotification($shop, self::REJECTED));

        return back()->with('error', "Tienda «{$shop->name}» rechazada. Su acceso al panel de vendedor queda bloqueado. Se notificó al vendedor.");
    }
}
