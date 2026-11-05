<?php

namespace App\Http\Controllers\Shop;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Product;
use App\Services\CartService;
use App\Services\CheckoutService;

class CartController extends Controller
{
    public function __construct(private CheckoutService $checkout, private CartService $cart)
    {
        $this->middleware('auth');
    }

    // ── Helpers privados ─────────────────────────────────────────────

    private function getCartQuery()
    {
        return Cart::where('user_id', auth()->id());
    }

    /**
     * Subtotal, envío y total ya formateados, para que cada respuesta AJAX
     * deje el resumen del carrito completo y no solo el subtotal.
     */
    private function summaryPayload(): array
    {
        $totals = $this->checkout->totals($this->getCartQuery()->get());

        return [
            'cart_total'    => number_format($totals['subtotal'], 2),
            'cart_shipping' => number_format($totals['shipping'], 2),
            'cart_tax'      => number_format($totals['tax'], 2),
            'cart_grand'    => number_format($totals['grand_total'], 2),
            'cart_count'    => $this->getCartQuery()->sum('quantity'),
        ];
    }

    // ── INDEX ─────────────────────────────────────────────────────────

    public function index()
    {
        $cartItems = $this->cart->items(auth()->user());
        $totals = $this->checkout->totals($cartItems);

        return view('cart.index', [
            'cartItems'     => $cartItems,
            'total'         => $totals['subtotal'],
            'shippingTotal' => $totals['shipping'],
            'taxTotal'      => $totals['tax'],
            'grandTotal'    => $totals['grand_total'],
        ]);
    }

    // ── MINI CART (dropdown header) ───────────────────────────────────

    public function mini()
    {
        $cartItems = $this->getCartQuery()->with('product.category')->get();
        $totals    = $this->checkout->totals($cartItems);
        $count     = $cartItems->sum('quantity');

        return response()->json([
            'count' => $count,
            'html'  => view('cart.mini', [
                'cartItems'     => $cartItems,
                'total'         => $totals['subtotal'],
                'shippingTotal' => $totals['shipping'],
                'grandTotal'    => $totals['grand_total'],
            ])->render(),
        ]);
    }

    // ── ADD ───────────────────────────────────────────────────────────

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'nullable|integer|min:1',
        ]);

        // Reglas en CartService (las mismas que la API): solo productos a la
        // venta, una combinación existente y sin pasar del stock.
        try {
            $this->cart->add(auth()->user(), (int) $request->product_id, $request->variation, (int) ($request->quantity ?? 1));
        } catch (BusinessRuleException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }

        return response()->json($this->summaryPayload() + [
            'status'  => 'success',
            'message' => __('Producto añadido al carrito'),
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

        return response()->json($this->summaryPayload() + [
            'status'  => 'success',
            'message' => __('Artículo quitado del carrito'),
        ]);
    }

    // ── MODAL ─────────────────────────────────────────────────────────

    /**
     * Ficha compacta para el modal de la tarjeta de producto, ya renderizada.
     *
     * Devolvía JSON mientras el JS del layout la inyectaba con .html(), así
     * que el modal escupía el JSON crudo en pantalla y desde la rejilla no
     * había forma de elegir talla ni color. El selector completo vive en la
     * ficha de producto; esto es el mismo selector en pequeño, alimentado por
     * Product::stockMap() para que ambos ofrezcan las mismas combinaciones.
     */
    public function modal(Request $request)
    {
        $productId = $request->input('product_id') ?? $request->input('id');
        abort_unless($productId, 422, 'product_id required');

        // La categoría entra por el rótulo de la talla ("Talla US", "Cintura").
        $product = Product::active()->with('stocks', 'category')->findOrFail($productId);

        return response()->view('partials.quick-add-modal', compact('product'));
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

        $quantity = $this->cart->setQuantity($item, (int) $request->quantity)->quantity;

        return response()->json($this->summaryPayload() + [
            'status'     => 'success',
            'subtotal'   => number_format($item->price * $quantity, 2),
            'limited_to' => $quantity,
        ]);
    }
}