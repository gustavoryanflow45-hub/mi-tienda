<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Notifications\OrderStatusUpdatedNotification;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ── GET /orders ──────────────────────────────────────────────
    public function index(Request $request)
    {
        // La categoría entra por la etiqueta de la talla ("Talla US" vs
        // "Cintura"), que sale del producto y, si no la declara, de ella.
        $query = Order::with(['orderDetails.product.category'])
            ->where('user_id', Auth::id());

        // Filtro estado de pago
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filtro estado de entrega
        if ($request->filled('delivery_status')) {
            $query->where('delivery_status', $request->delivery_status);
        }

        // Búsqueda por código
        if ($request->filled('code')) {
            $query->where('code', 'like', '%'.$request->code.'%');
        }

        // Filtro fecha inicio
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        // Filtro fecha fin
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

        // Avisos de cambio de estado sin leer (se marcan leídos al verlos)
        $statusUpdates = Auth::user()->unreadNotifications()
            ->where('type', OrderStatusUpdatedNotification::class)
            ->get();

        if ($statusUpdates->isNotEmpty()) {
            Auth::user()->unreadNotifications()
                ->where('type', OrderStatusUpdatedNotification::class)
                ->update(['read_at' => now()]);
        }

        return view('pages.orders', compact('orders', 'statusUpdates'));
    }

    // ── GET /orders/{id} ─────────────────────────────────────────
    public function show(int $id)
    {
        $user = Auth::user();
        $canSell = $user->isSeller();

        $order = Order::with(['orderDetails.product', 'orderDetails.product.category'])->findOrFail($id);

        // Comprador o vendedor con líneas en el pedido (OrderPolicy; a
        // cualquier otro le responde 404). El panel tiene su propio detalle.
        $this->authorize('view', $order);

        $isSeller = $canSell && $order->orderDetails->contains(fn ($d) => $d->seller_id === $user->id);

        return view('pages.order-detail', compact('order', 'isSeller'));
    }

    // ── POST /orders/{id}/send-to-warehouse ──────────────────────
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
