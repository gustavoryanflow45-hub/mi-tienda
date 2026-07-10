<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderArrivedWarehouseNotification;
use App\Notifications\OrderStatusUpdatedNotification;
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
        $query = Order::with(['orderDetails.product'])
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
        $canSell = in_array($user->user_type, ['seller', 'admin']);

        $query = Order::with(['orderDetails.product', 'orderDetails.product.category']);

        if ($user->user_type === 'admin') {
            // El admin (personal de almacén) puede ver cualquier pedido
        } elseif ($canSell) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('orderDetails', fn ($qq) => $qq->where('seller_id', $user->id));
            });
        } else {
            $query->where('user_id', $user->id);
        }

        $order = $query->findOrFail($id);

        $isSeller = $canSell && $order->orderDetails->contains(fn ($d) => $d->seller_id === $user->id);

        return view('pages.order-detail', compact('order', 'isSeller'));
    }

    // ── POST /orders/{id}/send-to-warehouse ──────────────────────
    public function sendToWarehouse(int $id)
    {
        $user = Auth::user();

        if (! in_array($user->user_type, ['seller', 'admin'])) {
            abort(403, 'Solo los vendedores pueden enviar pedidos al almacén.');
        }

        $order = Order::whereHas('orderDetails', fn ($q) => $q->where('seller_id', $user->id))
            ->findOrFail($id);

        if ($order->delivery_status !== 'confirmed') {
            return back()->with('warehouse_error', 'El pedido debe estar en estado "Confirmado" para enviarlo al almacén.');
        }

        // El almacén no puede despachar sin los datos del comprador:
        // intenta completar desde su dirección guardada / perfil antes de bloquear.
        if (! $order->hasCompleteShippingInfo() && $order->user) {
            $order->update(['shipping_address' => array_merge(
                $order->user->shippingSnapshot(),
                array_filter($order->shippingInfo(), fn ($v) => trim((string) $v) !== ''),
            )]);
        }

        if (! $order->hasCompleteShippingInfo()) {
            return back()->with('warehouse_error', 'No se puede enviar al almacén: faltan datos de envío del cliente (nombre, teléfono, correo, dirección o ciudad). Pide al cliente que complete su dirección de envío.');
        }

        $order->update([
            'delivery_status' => 'warehouse',
            'warehouse_at' => now(),
        ]);
        $order->orderDetails()->where('seller_id', $user->id)
            ->update(['delivery_status' => 'warehouse']);

        // Avisar al personal del almacén (admins) que llegó un pedido
        User::where('user_type', 'admin')->get()
            ->each(fn ($admin) => $admin->notify(new OrderArrivedWarehouseNotification($order, $user->name)));

        // Avisar al cliente que su pedido está en el almacén
        $order->user?->notify(new OrderStatusUpdatedNotification($order, 'warehouse'));

        return back()->with('warehouse_success', '¡Pedido enviado al almacén correctamente! El personal de despacho fue notificado.');
    }
}
