<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Notifications\OrderStatusUpdatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SellerOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (! in_array(Auth::user()->user_type, ['seller', 'admin'])) {
                abort(403, 'No autorizado.');
            }

            return $next($request);
        });
    }

    // ── GET /seller/orders ───────────────────────────────────────
    public function index(Request $request)
    {
        $sellerId = Auth::id();

        $query = Order::with(['user', 'orderDetails' => fn ($q) => $q->where('seller_id', $sellerId)])
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
    public function confirm(int $id)
    {
        $sellerId = Auth::id();

        $order = Order::whereHas('orderDetails', fn ($q) => $q->where('seller_id', $sellerId))
            ->findOrFail($id);

        if ($order->payment_status !== 'paid') {
            return back()->with('warehouse_error', 'El pedido aún no tiene el pago confirmado.');
        }

        if ($order->delivery_status !== 'pending') {
            return back()->with('warehouse_error', 'Solo se pueden confirmar pedidos en estado "Pendiente".');
        }

        $order->update([
            'delivery_status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
        $order->orderDetails()->where('seller_id', $sellerId)->update(['delivery_status' => 'confirmed']);

        $order->user?->notify(new OrderStatusUpdatedNotification($order, 'confirmed'));

        return back()->with('warehouse_success', 'Pedido #'.$order->code.' confirmado correctamente.');
    }
}
