<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Pedidos del comprador (los suyos, como /orders en la web). Las ventas de
 * un vendedor están en /seller/orders.
 */
class OrderController extends Controller
{
    // ── GET /api/v1/orders ───────────────────────────────────────
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'payment_status' => ['nullable', 'in:paid,unpaid'],
            'delivery_status' => ['nullable', 'in:pending,confirmed,warehouse,on_the_way,delivered'],
            'code' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $orders = Order::with('orderDetails.product.category')
            ->where('user_id', $request->user()->id)
            ->when($filters['payment_status'] ?? null, fn ($q, $v) => $q->where('payment_status', $v))
            ->when($filters['delivery_status'] ?? null, fn ($q, $v) => $q->where('delivery_status', $v))
            ->when($filters['code'] ?? null, fn ($q, $v) => $q->where('code', 'like', '%'.addcslashes($v, '%_\\').'%'))
            ->latest()
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return OrderResource::collection($orders);
    }

    // ── GET /api/v1/orders/{id} ──────────────────────────────────
    // Solo el comprador; cualquier otro, 404.
    public function show(Request $request, int $order): OrderResource
    {
        return new OrderResource(CheckoutController::loaded(CheckoutController::ownOrder($request, $order)));
    }
}
