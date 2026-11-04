<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Services\ShopApprovalService;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /** Estados de shops.status; la fuente es Shop::PENDING|APPROVED|REJECTED. */
    public const PENDING = Shop::PENDING;

    public const APPROVED = Shop::APPROVED;

    public const REJECTED = Shop::REJECTED;

    /**
     * Las rutas ya llevan 'auth:admin' + 'admin'; esto es defensa en
     * profundidad, igual que en los demás controladores del panel.
     */
    public function __construct()
    {
        $this->middleware(['auth:admin', 'admin']);
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
    public function approve(Request $request, int $id, ShopApprovalService $approvals)
    {
        $shop = Shop::with('user')->findOrFail($id);
        $this->authorize('approve', $shop);

        if (! $approvals->approve($shop, $request->user('admin'))) {
            return back()->with('warning', __('La tienda «:name» ya estaba aprobada.', ['name' => $shop->name]));
        }

        return back()->with('success', __('Tienda «:name» aprobada. Ya puede publicar productos. Se notificó al vendedor.', ['name' => $shop->name]));
    }

    // ── POST /admin/shops/{id}/reject ────────────────────────────
    public function reject(Request $request, int $id, ShopApprovalService $approvals)
    {
        $shop = Shop::with('user')->findOrFail($id);
        $this->authorize('reject', $shop);

        if (! $approvals->reject($shop, $request->user('admin'))) {
            return back()->with('warning', __('La tienda «:name» ya estaba rechazada.', ['name' => $shop->name]));
        }

        return back()->with('error', __('Tienda «:name» rechazada. Su acceso al panel de vendedor queda bloqueado. Se notificó al vendedor.', ['name' => $shop->name]));
    }
}
