<?php

namespace App\Http\Controllers;

use App\Models\Cart;

class MyProductsController extends Controller
{
    /**
     * "Mis productos": lo que el usuario fue agregando a su carrito, una
     * tarjeta por producto con las combinaciones (talla/color) que eligió.
     * GET /my-products
     *
     * Lee de carts, así que se vacía igual que el carrito cuando el pedido
     * se paga (Order::markPaid()).
     */
    public function index()
    {
        $lines = Cart::where('user_id', auth()->id())
            ->whereHas('product')
            ->with('product.category')   // describeVariant() necesita la categoría
            ->latest()
            ->get();

        // Una tarjeta por producto; el más recientemente agregado primero.
        $items = $lines->groupBy('product_id')->map(fn ($group) => [
            'product' => $group->first()->product,
            'lines' => $group,
            'quantity' => $group->sum('quantity'),
            'subtotal' => $group->sum(fn ($line) => $line->price * $line->quantity),
        ])->values();

        return view('pages.my-products', compact('items'));
    }
}
