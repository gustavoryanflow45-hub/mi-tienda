<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Notifications\OrderArrivedWarehouseNotification;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehouseController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:admin', 'admin']);
    }

    // ── GET /admin/warehouse ─────────────────────────────────────
    public function index(Request $request)
    {
        $query = Order::with(['user', 'orderDetails.product.category'])
            ->where('payment_status', 'paid');

        if ($request->filled('delivery_status')) {
            $query->where('delivery_status', $request->delivery_status);
        } else {
            // Por defecto el panel muestra el trabajo activo del almacén
            $query->whereIn('delivery_status', ['warehouse', 'on_the_way']);
        }

        if ($request->filled('code')) {
            $query->where('code', 'like', '%'.$request->code.'%');
        }

        $orders = $query->orderByRaw("CASE delivery_status WHEN 'warehouse' THEN 0 WHEN 'on_the_way' THEN 1 ELSE 2 END")
            ->latest('warehouse_at')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'warehouse' => Order::where('delivery_status', 'warehouse')->count(),
            'on_the_way' => Order::where('delivery_status', 'on_the_way')->count(),
            'delivered_today' => Order::where('delivery_status', 'delivered')
                ->whereDate('delivered_at', today())->count(),
        ];

        // Llegadas nuevas: notificaciones de almacén sin leer para este usuario
        $arrivals = Auth::guard('admin')->user()->unreadNotifications()
            ->where('type', OrderArrivedWarehouseNotification::class)
            ->get();

        // Al abrir el panel se dan por vistas
        if ($arrivals->isNotEmpty()) {
            Auth::guard('admin')->user()->unreadNotifications()
                ->where('type', OrderArrivedWarehouseNotification::class)
                ->update(['read_at' => now()]);
        }

        return view('admin.warehouse', compact('orders', 'stats', 'arrivals'));
    }

    // ── POST /admin/warehouse/orders/{id}/dispatch ───────────────
    public function dispatchOrder(Request $request, int $id, OrderFulfillmentService $fulfillment)
    {
        try {
            $order = $fulfillment->dispatch(Order::findOrFail($id), $request->user('admin'));
        } catch (BusinessRuleException $e) {
            return back()->with('warehouse_error', $e->getMessage());
        }

        return back()->with('warehouse_success', __('Pedido #:code despachado — ahora está en camino al cliente.', ['code' => $order->code]));
    }

    // ── POST /admin/warehouse/orders/{id}/deliver ────────────────
    public function deliver(Request $request, int $id, OrderFulfillmentService $fulfillment)
    {
        try {
            $order = $fulfillment->deliver(Order::findOrFail($id), $request->user('admin'));
        } catch (BusinessRuleException $e) {
            return back()->with('warehouse_error', $e->getMessage());
        }

        return back()->with('warehouse_success', __('Pedido #:code marcado como entregado.', ['code' => $order->code]));
    }
}
