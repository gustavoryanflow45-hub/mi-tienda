<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\SellerSettlement;
use App\Models\Wishlist;
use App\Notifications\SellerSettledNotification;
use App\Notifications\ShopStatusUpdatedNotification;
use App\Services\SettlementService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(SettlementService $settlements)
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

        // Aviso de liquidación acreditada en la billetera: mismo patrón, una vez.
        $settlementUpdates = $user->unreadNotifications()
                                  ->where('type', SellerSettledNotification::class)
                                  ->get();

        if ($settlementUpdates->isNotEmpty()) {
            $user->unreadNotifications()
                 ->where('type', SellerSettledNotification::class)
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
                'settlementUpdates',
            ));
        }

        // ── Usuario VERIFICADO → vista completa con stats ─────────
        // Las ventas se cuentan sobre las líneas del vendedor y solo cuando el
        // pedido está pagado: antes sumaba el grand_total completo de cualquier
        // pedido —incluidos los pendientes y lo vendido por otros vendedores—,
        // así que abrir el checkout sin pagar ya inflaba el total.
        //
        // Además solo cuentan las líneas ya entregadas y que el admin aún no
        // liquidó: cada liquidación (SettlementService) deja ventas y
        // ganancias en cero y empieza un ciclo nuevo. Las ganancias son las
        // ventas menos la comisión del marketplace (app.seller_commission_rate).
        $pending = $settlements->pendingFor($user->id);

        $paidLines = OrderDetail::where('seller_id', $user->id)
                                ->where('payment_status', 'paid');

        $stats = [
            'products'        => Product::where('added_by', $user->id)->count(),
            'total_sale'      => $pending['total_sales'],
            'total_profits'   => $pending['net_amount'],
            'commission_rate' => $pending['commission_rate'],
            'success_orders'  => (clone $paidLines)->where('delivery_status', 'delivered')
                                    ->distinct()->count('order_id'),
            'visitors'        => 0, // implementar con analytics si se desea
        ];

        $lastSettlement = SellerSettlement::where('seller_id', $user->id)
                                          ->latest('settled_at')
                                          ->first();

        return view('pages.dashboard-verified', compact('address', 'stats', 'shopUpdates', 'settlementUpdates', 'lastSettlement'));
    }
}