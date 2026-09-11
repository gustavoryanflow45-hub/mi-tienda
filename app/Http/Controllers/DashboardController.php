<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Wishlist;
use App\Notifications\ShopStatusUpdatedNotification;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user    = Auth::user();
        $address = Address::where('user_id', $user->id)
                          ->where('is_default', 1)
                          ->first();

        // Aviso de aprobación/rechazo de tienda: se muestra una vez y se marca leído.
        $shopUpdates = $user->unreadNotifications()
                            ->where('type', ShopStatusUpdatedNotification::class)
                            ->get();

        if ($shopUpdates->isNotEmpty()) {
            $user->unreadNotifications()
                 ->where('type', ShopStatusUpdatedNotification::class)
                 ->update(['read_at' => now()]);
        }

        // ── Usuario NO verificado → vista simple ─────────────────
        if (!$user->isVerified()) {
            $cartCount     = session('cart') ? count(session('cart')) : 0;
            $wishlistCount = Wishlist::where('user_id', $user->id)->count();
            $orderCount    = Order::where('user_id', $user->id)->count();

            return view('pages.dashboard-index', compact(
                'address',
                'cartCount',
                'wishlistCount',
                'orderCount',
                'shopUpdates',
            ));
        }

        // ── Usuario VERIFICADO → vista completa con stats ─────────
        // Las ventas se cuentan sobre las líneas del vendedor y solo cuando el
        // pedido está pagado: antes sumaba el grand_total completo de cualquier
        // pedido —incluidos los pendientes y lo vendido por otros vendedores—,
        // así que abrir el checkout sin pagar ya inflaba el total.
        $soldLines = OrderDetail::where('seller_id', $user->id)
                                ->where('payment_status', 'paid');

        $stats = [
            'products'       => Product::where('added_by', $user->id)->count(),
            'total_sale'     => (clone $soldLines)
                                    ->selectRaw('COALESCE(SUM((price * quantity) + shipping_cost + tax - discount_on_product), 0) AS total')
                                    ->value('total'),
            'total_profits'  => (clone $soldLines)
                                    ->selectRaw('COALESCE(SUM((price * quantity) - discount_on_product), 0) AS total')
                                    ->value('total'),
            'success_orders' => (clone $soldLines)->where('delivery_status', 'delivered')
                                    ->distinct()->count('order_id'),
            'visitors'       => 0, // implementar con analytics si se desea
        ];

        return view('pages.dashboard-verified', compact('address', 'stats', 'shopUpdates'));
    }
}