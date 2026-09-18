<?php

namespace App\Http\Controllers;

use App\Models\SellerSettlement;
use App\Models\User;
use App\Services\SettlementService;
use Illuminate\Http\Request;

/**
 * Liquidación de ventas a los vendedores.
 *
 * El admin ve, por tienda, lo vendido desde la última liquidación, la
 * comisión que retiene el marketplace y el neto a pagar; al liquidar, esas
 * ventas quedan cerradas y el panel del vendedor vuelve a cero.
 */
class AdminSettlementController extends Controller
{
    public function __construct(private SettlementService $settlements)
    {
        // Las rutas ya llevan 'auth' + 'admin'; defensa en profundidad como
        // en AdminShopController.
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (! $request->user()?->isAdmin()) {
                abort(403, 'No autorizado.');
            }

            return $next($request);
        });
    }

    // ── GET /admin/settlements ───────────────────────────────────
    public function index(Request $request)
    {
        $pending = $this->settlements->pendingForAll();

        $query = User::query()
            ->where('user_type', 'seller')
            ->with('shop')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhereHas('shop', fn ($qq) => $qq->where('name', 'like', '%'.$search.'%'));
            });
        }

        $sellers = $query->get();

        $lastSettlements = SellerSettlement::query()
            ->whereIn('seller_id', $sellers->pluck('id'))
            ->orderByDesc('settled_at')
            ->get()
            ->unique('seller_id')
            ->keyBy('seller_id');

        $empty = [
            'total_sales' => 0.0,
            'commission_rate' => $this->settlements->commissionRate(),
            'commission' => 0.0,
            'net_amount' => 0.0,
            'lines_count' => 0,
        ];

        // Los que tienen algo pendiente van primero: son los que hay que pagar.
        $rows = $sellers->map(fn (User $seller) => [
            'seller' => $seller,
            'pending' => $pending[$seller->id] ?? $empty,
            'last' => $lastSettlements->get($seller->id),
        ])->sortByDesc(fn ($row) => $row['pending']['total_sales'])->values();

        $totals = [
            'sellers_pending' => $rows->filter(fn ($row) => $row['pending']['lines_count'] > 0)->count(),
            'total_sales' => $rows->sum(fn ($row) => $row['pending']['total_sales']),
            'commission' => $rows->sum(fn ($row) => $row['pending']['commission']),
            'net_amount' => $rows->sum(fn ($row) => $row['pending']['net_amount']),
        ];

        $history = SellerSettlement::with(['seller.shop', 'admin'])
            ->orderByDesc('settled_at')
            ->paginate(15, ['*'], 'page')
            ->withQueryString();

        $commissionRate = $this->settlements->commissionRate();

        return view('admin.settlements', compact('rows', 'totals', 'history', 'commissionRate'));
    }

    // ── POST /admin/settlements/{seller} ─────────────────────────
    public function settle(Request $request, int $seller)
    {
        $seller = User::where('user_type', 'seller')->with('shop')->findOrFail($seller);
        $label = $seller->shop?->name ?? $seller->name;

        $settlement = $this->settlements->settle($seller, $request->user());

        if (! $settlement) {
            return back()->with('warning', "«{$label}» no tiene ventas pendientes de liquidar.");
        }

        return back()->with('success', sprintf(
            'Liquidación de «%s» registrada: $%s vendidos, $%s de comisión (%d%%), $%s a pagar al vendedor. Su panel vuelve a cero.',
            $label,
            number_format($settlement->total_sales, 2),
            number_format($settlement->commission, 2),
            round($settlement->commission_rate * 100),
            number_format($settlement->net_amount, 2),
        ));
    }
}
