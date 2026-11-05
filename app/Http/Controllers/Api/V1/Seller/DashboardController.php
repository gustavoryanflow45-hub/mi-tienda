<?php

namespace App\Http\Controllers\Api\V1\Seller;

use App\Http\Controllers\Controller;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\SellerSettlement;
use App\Services\SettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Las cifras del panel web del vendedor (DashboardController): ventas y
 * ganancias del ciclo de liquidación en curso — de SettlementService, así
 * que son exactamente las que pagará el admin —, pedidos por atender y la
 * última liquidación.
 */
class DashboardController extends Controller
{
    // ── GET /api/v1/seller/dashboard ─────────────────────────────
    public function __invoke(Request $request, SettlementService $settlements): JsonResponse
    {
        $seller = $request->user();
        $pending = $settlements->pendingFor($seller->id);
        $paidLines = OrderDetail::where('seller_id', $seller->id)->where('payment_status', 'paid');
        $last = SellerSettlement::where('seller_id', $seller->id)->latest('settled_at')->first();

        return response()->json(['data' => [
            'shop' => ['id' => $seller->shop->id, 'name' => $seller->shop->name],
            'currency' => 'USD',
            'products' => Product::where('added_by', $seller->id)->count(),
            // Pagados y aún sin confirmar: lo que el vendedor tiene que atender.
            'pending_orders' => (clone $paidLines)->where('delivery_status', 'pending')->distinct()->count('order_id'),
            'delivered_orders' => (clone $paidLines)->where('delivery_status', 'delivered')->distinct()->count('order_id'),
            // Ciclo en curso: líneas pagadas, entregadas y aún sin liquidar.
            'current_cycle' => [
                'total_sales' => $pending['total_sales'],
                'commission_rate' => $pending['commission_rate'],
                'commission' => $pending['commission'],
                'net_amount' => $pending['net_amount'],
                'lines' => $pending['lines_count'],
            ],
            'last_settlement' => $last ? [
                'net_amount' => round((float) $last->net_amount, 2),
                'settled_at' => $last->settled_at?->toIso8601String(),
            ] : null,
            'balance' => round((float) $seller->balance, 2),
        ]]);
    }
}
