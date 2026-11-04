<?php

namespace App\Http\Controllers\Seller;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (! Auth::user()->isSeller()) {
                abort(403, __('No autorizado.'));
            }

            return $next($request);
        });
    }

    // ── GET /seller/orders ───────────────────────────────────────
    public function index(Request $request)
    {
        $sellerId = Auth::id();

        $query = Order::with(['user', 'orderDetails' => fn ($q) => $q->where('seller_id', $sellerId)->with('product.category')])
            ->whereHas('orderDetails', fn ($q) => $q->where('seller_id', $sellerId))
            ->where('payment_status', 'paid');

        if ($request->filled('delivery_status')) {
            $query->where('delivery_status', $request->delivery_status);
        }

        if ($request->filled('code')) {
            $query->where('code', 'like', '%'.$request->code.'%');
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

        // Mark all unread order notifications as read when seller views this page
        Auth::user()->unreadNotifications()
            ->where('type', \App\Notifications\SellerNewOrderNotification::class)
            ->update(['read_at' => now()]);

        return view('pages.seller-orders', compact('orders'));
    }

    // ── POST /seller/orders/{id}/confirm ─────────────────────────
    public function confirm(int $id, OrderFulfillmentService $fulfillment)
    {
        $order = Order::findOrFail($id);
        // Solo un vendedor con líneas en el pedido (OrderPolicy; si no, 404).
        $this->authorize('confirm', $order);

        try {
            $order = $fulfillment->confirm($order, Auth::user());
        } catch (BusinessRuleException $e) {
            return back()->with('warehouse_error', $e->getMessage());
        }

        return back()->with('warehouse_success', __('Pedido #:code confirmado correctamente.', ['code' => $order->code]));
    }

    // ── POST /seller/orders/{id}/send-to-warehouse ───────────────
    public function sendToWarehouse(int $id, OrderFulfillmentService $fulfillment)
    {
        $user = Auth::user();

        if (! $user->isSeller()) {
            abort(403, __('Solo los vendedores pueden enviar pedidos al almacén.'));
        }

        $order = Order::findOrFail($id);
        $this->authorize('sendToWarehouse', $order);

        try {
            $fulfillment->sendToWarehouse($order, $user);
        } catch (BusinessRuleException $e) {
            return back()->with('warehouse_error', $e->getMessage());
        }

        return back()->with('warehouse_success', __('¡Pedido enviado al almacén correctamente! El personal de despacho fue notificado.'));
    }
}
