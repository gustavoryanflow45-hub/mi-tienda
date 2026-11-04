<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

/**
 * Pedidos de todos los vendedores (/admin/orders), solo lectura: el
 * almacén (Admin\WarehouseController) es quien los mueve.
 *
 * Antes el admin veía cualquier pedido desde /orders/{id} de la tienda;
 * ahora ese poder vive solo aquí.
 */
class OrderController extends Controller
{
    public function __construct()
    {
        // Las rutas ya llevan 'auth:admin' + 'admin'; defensa en profundidad.
        $this->middleware(['auth:admin', 'admin']);
    }

    // ── GET /admin/orders ────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Order::with('user')->withCount('orderDetails');

        if ($request->filled('code')) {
            $query->where('code', 'like', '%'.$request->code.'%');
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('delivery_status')) {
            $query->where('delivery_status', $request->delivery_status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()->paginate(20)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    // ── GET /admin/orders/{id} ───────────────────────────────────
    public function show(int $id)
    {
        $order = Order::with(['user', 'dispatcher', 'orderDetails.product.category', 'orderDetails.seller.shop'])
            ->findOrFail($id);

        $this->authorize('view', $order);

        return view('admin.orders.show', compact('order'));
    }
}
