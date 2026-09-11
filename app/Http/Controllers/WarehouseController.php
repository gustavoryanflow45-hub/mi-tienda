<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Notifications\OrderArrivedWarehouseNotification;
use App\Notifications\OrderStatusUpdatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehouseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Defensa en profundidad: las rutas ya llevan el middleware 'admin'.
        $this->middleware(function ($request, $next) {
            if (! Auth::user()->isAdmin()) {
                abort(403, 'No autorizado.');
            }

            return $next($request);
        });
    }

    // ── GET /warehouse ───────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Order::with(['user', 'orderDetails'])
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
        $arrivals = Auth::user()->unreadNotifications()
            ->where('type', OrderArrivedWarehouseNotification::class)
            ->get();

        // Al abrir el panel se dan por vistas
        if ($arrivals->isNotEmpty()) {
            Auth::user()->unreadNotifications()
                ->where('type', OrderArrivedWarehouseNotification::class)
                ->update(['read_at' => now()]);
        }

        return view('pages.warehouse', compact('orders', 'stats', 'arrivals'));
    }

    // ── POST /warehouse/orders/{id}/dispatch ─────────────────────
    public function dispatchOrder(int $id)
    {
        $order = Order::findOrFail($id);

        if ($order->delivery_status !== 'warehouse') {
            return back()->with('warehouse_error', 'Solo se pueden despachar pedidos que están en el almacén.');
        }

        $order->update([
            'delivery_status' => 'on_the_way',
            'dispatched_at' => now(),
            'dispatched_by' => Auth::id(),
        ]);
        $order->orderDetails()->update(['delivery_status' => 'on_the_way']);

        $order->user?->notify(new OrderStatusUpdatedNotification($order, 'on_the_way'));

        return back()->with('warehouse_success', 'Pedido #'.$order->code.' despachado — ahora está en camino al cliente.');
    }

    // ── POST /warehouse/orders/{id}/deliver ──────────────────────
    public function deliver(int $id)
    {
        $order = Order::findOrFail($id);

        if ($order->delivery_status !== 'on_the_way') {
            return back()->with('warehouse_error', 'Solo se pueden marcar como entregados los pedidos que están en camino.');
        }

        $order->update([
            'delivery_status' => 'delivered',
            'delivered_at' => now(),
        ]);
        $order->orderDetails()->update(['delivery_status' => 'delivered']);

        $order->user?->notify(new OrderStatusUpdatedNotification($order, 'delivered'));

        return back()->with('warehouse_success', 'Pedido #'.$order->code.' marcado como entregado.');
    }
}
