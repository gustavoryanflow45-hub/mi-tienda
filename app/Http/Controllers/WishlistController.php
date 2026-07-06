<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $wishlist = Wishlist::where('user_id', auth()->id())->with('product')->get();
        return view('pages.wishlist', compact('wishlist'));
    }

    public function add(Request $request)
    {
        $productId = $request->input('product_id') ?? $request->input('id');
        abort_unless($productId, 422, 'product_id required');

        Wishlist::firstOrCreate([
            'user_id'    => auth()->id(),
            'product_id' => $productId,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Añadido a favoritos.']);
    }

    public function remove(Request $request)
    {
        $productId = $request->input('product_id') ?? $request->input('id');
        abort_unless($productId, 422, 'product_id required');

        Wishlist::where('user_id', auth()->id())
            ->where('product_id', $productId)
            ->delete();

        return response()->json(['status' => 'success', 'message' => 'Eliminado de favoritos.']);
    }
}
