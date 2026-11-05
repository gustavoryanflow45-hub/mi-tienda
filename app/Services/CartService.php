<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Reglas del carrito: qué combinación se puede añadir, con qué precio y
 * hasta cuántas unidades. Las usan el carrito web (CartController) y la API,
 * así que la app no puede meter en el carrito nada que la web rechace.
 *
 * Cuando algo no procede lanza BusinessRuleException con un mensaje ya
 * traducido: la web lo devuelve como {status: 'error'} y la API como 422.
 */
class CartService
{
    /** Tope cuando una línea no tiene fila de stock (datos antiguos). */
    public const UNTRACKED_STOCK = 999;

    /**
     * Ítems del carrito con producto, stocks y categoría (la etiqueta de la
     * talla sale de ella) y `available_stock`: lo que queda de su combinación.
     */
    public function items(User $user): Collection
    {
        $items = Cart::where('user_id', $user->id)->with(['product.stocks', 'product.category'])->get();

        $items->each(function (Cart $item) {
            $stock = $item->variation
                ? $item->product?->stocks->where('variant', $item->variation)->first()
                : $item->product?->stocks->first();
            $item->available_stock = $stock ? $stock->qty : self::UNTRACKED_STOCK;
        });

        return $items;
    }

    /**
     * Añade $quantity unidades de la combinación $variation (null o '' en un
     * producto sin variantes). Si la línea ya existe suma, con tope en el
     * stock. Solo productos a la venta (Product::scopeActive): uno vetado o
     * despublicado es un 404.
     */
    public function add(User $user, int $productId, ?string $variation, int $quantity = 1): Cart
    {
        $product = Product::active()->findOrFail($productId);
        $variation = filled($variation) ? $variation : null;

        $stock = $variation
            ? ProductStock::where('product_id', $product->id)->where('variant', $variation)->first()
            : ProductStock::where('product_id', $product->id)->whereNull('variant')->first();

        // Un producto con variantes exige una combinación que exista. Antes,
        // una variación desconocida dejaba $stock en null y las validaciones
        // de abajo se saltaban enteras: se agregaba a precio base y sin tope.
        if ($product->load('stocks')->hasVariants() && ! $stock) {
            throw new BusinessRuleException($variation
                ? __('Esa combinación no está disponible.')
                : __('Elige talla y color antes de agregar al carrito.'));
        }

        if (! $stock || $stock->qty <= 0) {
            throw new BusinessRuleException(__('Producto agotado.'));
        }

        if ($quantity > $stock->qty) {
            throw new BusinessRuleException(__('Solo hay :max unidades en stock', ['max' => $stock->qty]));
        }

        $existing = Cart::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('variation', $variation)
            ->first();

        if ($existing) {
            $existing->update(['quantity' => min($existing->quantity + $quantity, $stock->qty)]);

            return $existing;
        }

        return Cart::create([
            'user_id' => $user->id,
            'temp_user_id' => null,
            'product_id' => $product->id,
            'product_stock_id' => $stock->id,
            'variation' => $variation,
            'quantity' => $quantity,
            // Product::finalPrice(): la misma regla de descuento que la ficha y la API.
            'price' => $product->finalPrice((float) $stock->price),
            'tax' => 0,
            'shipping_cost' => $product->shipping_cost ?? 0,
        ]);
    }

    /** Cambia las unidades de la línea, con tope en el stock de su combinación. */
    public function setQuantity(Cart $item, int $quantity): Cart
    {
        $stock = $item->variation
            ? ProductStock::where('product_id', $item->product_id)->where('variant', $item->variation)->first()
            : ProductStock::where('product_id', $item->product_id)->first();

        $item->update(['quantity' => min($quantity, $stock ? $stock->qty : self::UNTRACKED_STOCK)]);

        return $item;
    }

    /** Vacía el carrito del usuario. */
    public function clear(User $user): void
    {
        Cart::where('user_id', $user->id)->delete();
    }
}
