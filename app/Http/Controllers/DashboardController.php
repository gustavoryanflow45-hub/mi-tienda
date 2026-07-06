<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Product;
use App\Models\Order;
use App\Models\Wishlist;
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
            ));
        }

        // ── Usuario VERIFICADO → vista completa con stats ─────────
        $stats = [
            'products'       => Product::where('added_by', $user->id)->count(),
            'total_sale'     => Order::whereHas('orderDetails', fn($q) => $q->where('seller_id', $user->id))
                                     ->sum('grand_total'),
            'total_profits'  => Order::whereHas('orderDetails', fn($q) => $q->where('seller_id', $user->id)
                                     ->where('payment_status', 'paid'))
                                     ->sum('grand_total'),
            'success_orders' => Order::whereHas('orderDetails', fn($q) => $q->where('seller_id', $user->id)
                                     ->where('delivery_status', 'delivered'))
                                     ->count(),
            'visitors'       => 0, // implementar con analytics si se desea
        ];

        return view('pages.dashboard-verified', compact('address', 'stats'));
    }
}