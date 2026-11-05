<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CartItemResource;
use App\Models\Cart;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Carrito del usuario del token. Es el mismo carrito que ve en la web
 * (tabla carts) y las mismas reglas (CartService): una combinación que la
 * web rechaza tampoco entra desde la app. Cada escritura devuelve el carrito
 * completo, para que la app no tenga que volver a pedirlo.
 */
class CartController extends Controller
{
    public function __construct(
        private CartService $cart,
        private CheckoutService $checkout,
    ) {}

    // ── GET /api/v1/cart ─────────────────────────────────────────
    public function show(Request $request): JsonResponse
    {
        return $this->cartResponse($request);
    }

    // ── POST /api/v1/cart/items ──────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'variation' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        $this->cart->add($request->user(), $data['product_id'], $data['variation'] ?? null, $data['quantity'] ?? 1);

        return $this->cartResponse($request, __('Producto añadido al carrito'), 201);
    }

    // ── PATCH /api/v1/cart/items/{id} ────────────────────────────
    // La cantidad se limita al stock de la combinación; la respuesta trae la real.
    public function update(Request $request, int $item): JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:999']]);

        $this->cart->setQuantity($this->item($request, $item), $data['quantity']);

        return $this->cartResponse($request);
    }

    // ── DELETE /api/v1/cart/items/{id} ───────────────────────────
    public function destroy(Request $request, int $item): JsonResponse
    {
        $this->item($request, $item)->delete();

        return $this->cartResponse($request, __('Artículo quitado del carrito'));
    }

    // ── DELETE /api/v1/cart ──────────────────────────────────────
    public function clear(Request $request): Response
    {
        $this->cart->clear($request->user());

        return response()->noContent();
    }

    /** Línea del carrito del usuario; la de otro es un 404, no un 403. */
    private function item(Request $request, int $id): Cart
    {
        return Cart::where('user_id', $request->user()->id)->findOrFail($id);
    }

    private function cartResponse(Request $request, ?string $message = null, int $status = 200): JsonResponse
    {
        $items = $this->cart->items($request->user());

        return response()->json(array_filter([
            'data' => [
                'items' => CartItemResource::collection($items)->resolve($request),
                'count' => (int) $items->sum('quantity'),
                'totals' => $this->checkout->totals($items),
                'currency' => 'USD',
            ],
            'message' => $message,
        ]), $status);
    }
}
