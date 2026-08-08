<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductStock;

class CartController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ── Helpers privados ─────────────────────────────────────────────

    private function getCartQuery()
    {
        return Cart::where('user_id', auth()->id());
    }

    private function cartTotal()
    {
        return $this->getCartQuery()->get()->sum(fn($item) => $item->price * $item->quantity);
    }

    // ── INDEX ─────────────────────────────────────────────────────────

    public function index()
    {
        $cartItems = $this->getCartQuery()->with(['product.stocks'])->get();

        $cartItems->each(function ($item) {
            $stock = $item->variation
                ? $item->product->stocks->where('variant', $item->variation)->first()
                : $item->product->stocks->first();
            $item->available_stock = $stock ? $stock->qty : 999;
        });

        $total = $cartItems->sum(fn($item) => $item->price * $item->quantity);

        return view('cart.index', compact('cartItems', 'total'));
    }

    // ── MINI CART (dropdown header) ───────────────────────────────────

    public function mini()
    {
        $cartItems = $this->getCartQuery()->with('product')->get();
        $total     = $cartItems->sum(fn($item) => $item->price * $item->quantity);
        $count     = $cartItems->sum('quantity');

        return response()->json([
            'count' => $count,
            'html'  => view('cart.mini', compact('cartItems', 'total'))->render(),
        ]);
    }

    // ── ADD ───────────────────────────────────────────────────────────

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'nullable|integer|min:1',
        ]);

        $product  = Product::findOrFail($request->product_id);
        $quantity = $request->quantity ?? 1;
        $variant  = $request->variation ?? null;

        $stock = $variant
            ? ProductStock::where('product_id', $product->id)->where('variant', $variant)->first()
            : ProductStock::where('product_id', $product->id)->whereNull('variant')->first();

        // Un producto con variantes exige una combinación que exista. Antes,
        // una variación desconocida dejaba $stock en null y las validaciones
        // de abajo se saltaban enteras: se agregaba a precio base y sin tope.
        if ($product->load('stocks')->hasVariants() && ! $stock) {
            return response()->json([
                'status'  => 'error',
                'message' => $variant
                    ? 'Esa combinación no está disponible.'
                    : 'Elige talla y color antes de agregar al carrito.',
            ]);
        }

        // Validar stock disponible
        if (! $stock || $stock->qty <= 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Producto agotado.',
            ]);
        }

        if ($quantity > $stock->qty) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Not enough stock. Only ' . $stock->qty . ' available.',
            ]);
        }

        $price = $stock ? $stock->price : $product->unit_price;

        if ($product->discount > 0) {
            $price = $product->discount_type === 'percent'
                ? $price * (1 - $product->discount / 100)
                : $price - $product->discount;
        }

        $existing = $this->getCartQuery()
            ->where('product_id', $product->id)
            ->where('variation', $variant)
            ->first();

        if ($existing) {
            $newQty = $existing->quantity + $quantity;
            if ($stock && $newQty > $stock->qty) {
                $newQty = $stock->qty;
            }
            $existing->update(['quantity' => $newQty]);
        } else {
            Cart::create([
                'user_id'          => auth()->id(),
                'temp_user_id'     => null,
                'product_id'       => $product->id,
                'product_stock_id' => $stock?->id,
                'variation'        => $variant,
                'quantity'         => $quantity,
                'price'            => $price,
                'tax'              => 0,
                'shipping_cost'    => $product->shipping_cost ?? 0,
            ]);
        }

        $cartCount = $this->getCartQuery()->sum('quantity');
        $cartTotal = $this->cartTotal();

        return response()->json([
            'status'     => 'success',
            'message'    => 'Product added to cart',
            'cart_count' => $cartCount,
            'cart_total' => number_format($cartTotal, 2),
        ]);
    }

    // ── REMOVE ────────────────────────────────────────────────────────

    public function remove(Request $request)
    {
        $request->validate(['cart_id' => 'required|exists:carts,id']);

        $item = Cart::findOrFail($request->cart_id);

        if ($item->user_id !== auth()->id()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $item->delete();

        $cartCount = $this->getCartQuery()->sum('quantity');
        $cartTotal = $this->cartTotal();

        return response()->json([
            'status'     => 'success',
            'message'    => 'Item removed',
            'cart_count' => $cartCount,
            'cart_total' => number_format($cartTotal, 2),
        ]);
    }

    // ── MODAL ─────────────────────────────────────────────────────────

    public function modal(Request $request)
    {
        $productId = $request->input('product_id') ?? $request->input('id');
        abort_unless($productId, 422, 'product_id required');

        $product = Product::with('stocks')->findOrFail($productId);

        return response()->json([
            'status'  => 'success',
            'product' => [
                'id'          => $product->id,
                'name'        => $product->name,
                'slug'        => $product->slug,
                'price'       => number_format($product->unit_price, 2),
                'final_price' => number_format($product->discounted_price, 2),
                'discount'    => $product->discount,
                'thumbnail'   => $product->thumbnail
                    ? asset('storage/' . $product->thumbnail)
                    : 'https://via.placeholder.com/300x300/f8f9fa/679941?text=Producto',
                'rating'      => $product->rating,
                'stocks'      => $product->stocks->map(fn($s) => [
                    'variant' => $s->variant,
                    'price'   => $s->price,
                    'qty'     => $s->qty,
                ]),
            ],
        ]);
    }

    // ── UPDATE QUANTITY ───────────────────────────────────────────────

    public function updateQuantity(Request $request)
    {
        $request->validate([
            'cart_id'  => 'required|exists:carts,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $item = Cart::findOrFail($request->cart_id);

        if ($item->user_id !== auth()->id()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $stock = $item->variation
            ? ProductStock::where('product_id', $item->product_id)->where('variant', $item->variation)->first()
            : ProductStock::where('product_id', $item->product_id)->first();

        $maxQty   = $stock ? $stock->qty : 999;
        $quantity = min($request->quantity, $maxQty);

        $item->update(['quantity' => $quantity]);

        $cartTotal = $this->cartTotal();
        $cartCount = $this->getCartQuery()->sum('quantity');

        return response()->json([
            'status'     => 'success',
            'subtotal'   => number_format($item->price * $quantity, 2),
            'cart_total' => number_format($cartTotal, 2),
            'cart_count' => $cartCount,
            'limited_to' => $quantity,
        ]);
    }
}