<?php

namespace App\Http\Controllers\Api\V1\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SellerOrderResource;
use App\Models\Order;
use App\Notifications\SellerNewOrderNotification;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Ventas del vendedor del token: pedidos pagados con alguna línea suya, como
 * /seller/orders en la web. Confirmar y enviar al almacén pasan por
 * OrderFulfillmentService y OrderPolicy, igual que en la web; un pedido sin
 * líneas suyas es 404.
 */
class OrderController extends Controller
{
    // ── GET /api/v1/seller/orders ────────────────────────────────
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'delivery_status' => ['nullable', 'in:pending,confirmed,warehouse,on_the_way,delivered'],
            'code' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $seller = $request->user();

        $orders = $this->sellerOrders($seller->id)
            ->when($filters['delivery_status'] ?? null, fn ($q, $v) => $q->where('delivery_status', $v))
            ->when($filters['code'] ?? null, fn ($q, $v) => $q->where('code', 'like', '%'.addcslashes($v, '%_\\').'%'))
            ->latest()
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        // Como en la web: ver el listado da por leídos los avisos de venta nueva.
        $seller->unreadNotifications()->where('type', SellerNewOrderNotification::class)->update(['read_at' => now()]);

        return SellerOrderResource::collection($orders);
    }

    // ── GET /api/v1/seller/orders/{id} ───────────────────────────
    public function show(Request $request, int $order): SellerOrderResource
    {
        return new SellerOrderResource($this->own($request, $order));
    }

    // ── POST /api/v1/seller/orders/{id}/confirm ──────────────────
    public function confirm(Request $request, int $order, OrderFulfillmentService $fulfillment): SellerOrderResource
    {
        $model = $this->own($request, $order);
        $this->authorize('confirm', $model);

        $fulfillment->confirm($model, $request->user());

        return new SellerOrderResource($this->own($request, $order));
    }

    // ── POST /api/v1/seller/orders/{id}/send-to-warehouse ────────
    public function sendToWarehouse(Request $request, int $order, OrderFulfillmentService $fulfillment): SellerOrderResource
    {
        $model = $this->own($request, $order);
        $this->authorize('sendToWarehouse', $model);

        $fulfillment->sendToWarehouse($model, $request->user());

        return new SellerOrderResource($this->own($request, $order));
    }

    /**
     * Pedidos pagados con líneas del vendedor, cargando solo esas líneas. Un
     * pedido sin pagar es un checkout en curso, no una venta: no se muestra.
     */
    private function sellerOrders(int $sellerId)
    {
        return Order::with([
            'user',
            'orderDetails' => fn ($q) => $q->where('seller_id', $sellerId)->with('product.category'),
        ])
            ->where('payment_status', 'paid')
            ->whereHas('orderDetails', fn ($q) => $q->where('seller_id', $sellerId));
    }

    private function own(Request $request, int $id): Order
    {
        return $this->sellerOrders($request->user()->id)->findOrFail($id);
    }
}
