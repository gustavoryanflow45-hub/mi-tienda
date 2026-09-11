<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Precios del carrito y creación del pedido pendiente.
 *
 * El pedido se materializa aquí, en el momento en que el comprador inicia
 * el pago, y no al abrir /checkout: así volver atrás desde el checkout no
 * deja pedidos fantasma en el historial del cliente ni en las ventas del
 * vendedor.
 */
class CheckoutService
{
    /** Clave de sesión con el pedido pendiente en curso. */
    public const ORDER_SESSION_KEY = 'checkout_order_id';

    /** Clave de sesión con los datos de envío capturados en el checkout. */
    public const SHIPPING_SESSION_KEY = 'checkout_shipping';

    /** Ítems del carrito del usuario, con su producto cargado. */
    public function items(User $user): Collection
    {
        return Cart::where('user_id', $user->id)->with('product')->get();
    }

    /**
     * Desglose del pedido: subtotal, envío, impuesto y total.
     *
     * El envío es el costo fijo que el vendedor define por producto
     * (`products.shipping_cost`, copiado a `carts.shipping_cost` al agregar
     * al carrito) y se cobra una vez por línea, no por unidad — igual que
     * como ya se suma en el detalle del pedido.
     */
    public function totals(Collection $items): array
    {
        $subtotal = round($items->sum(fn ($i) => (float) $i->price * $i->quantity), 2);
        $shipping = round($items->sum(fn ($i) => (float) $i->shipping_cost), 2);
        $tax = round($items->sum(fn ($i) => (float) $i->tax), 2);

        return [
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'tax' => $tax,
            'grand_total' => round($subtotal + $shipping + $tax, 2),
        ];
    }

    /** Totales del carrito del usuario, sin tener que cargar los ítems fuera. */
    public function totalsFor(User $user): array
    {
        return $this->totals($this->items($user));
    }

    /**
     * Datos de envío a guardar en el pedido: lo que el comprador acaba de
     * escribir en el checkout manda sobre lo que hay en su perfil.
     */
    public function shippingSnapshot(User $user): array
    {
        return array_merge(
            $user->shippingSnapshot(),
            $this->filled((array) session(self::SHIPPING_SESSION_KEY, [])),
        );
    }

    /**
     * Pedido pendiente listo para cobrar, creado a partir del carrito actual.
     * Reutiliza el de la sesión si sigue sin pagarse, para no acumular uno por
     * cada intento de pago. Devuelve null si el carrito está vacío.
     */
    public function pendingOrderFor(User $user): ?Order
    {
        $items = $this->items($user);

        if ($items->isEmpty()) {
            return null;
        }

        $totals = $this->totals($items);
        $order = $this->reusableOrderFor($user);

        if ($order) {
            $order->update([
                'subtotal' => $totals['subtotal'],
                'shipping_total' => $totals['shipping'],
                'tax_amount' => $totals['tax'],
                'grand_total' => $totals['grand_total'],
                'shipping_address' => $this->shippingSnapshot($user),
            ]);
            $order->orderDetails()->delete();
        } else {
            $order = Order::create([
                'user_id' => $user->id,
                'session_id' => session()->getId(),
                'code' => 'ORD-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -6)),
                'status' => 'pendiente',
                'payment_status' => 'unpaid',
                'delivery_status' => 'pending',
                'subtotal' => $totals['subtotal'],
                'shipping_total' => $totals['shipping'],
                'tax_amount' => $totals['tax'],
                'grand_total' => $totals['grand_total'],
                'shipping_address' => $this->shippingSnapshot($user),
            ]);
        }

        foreach ($items as $item) {
            $order->orderDetails()->create([
                'seller_id' => $item->product->added_by ?? null,
                'product_id' => $item->product_id,
                'variation' => $item->variation,
                'product_name' => $item->product->name ?? 'Producto',
                'price' => $item->price,
                'quantity' => $item->quantity,
                'tax' => $item->tax ?? 0,
                'shipping_cost' => $item->shipping_cost ?? 0,
                'discount_on_product' => 0,
                'delivery_status' => 'pending',
                'payment_status' => 'unpaid',
            ]);
        }

        session([self::ORDER_SESSION_KEY => $order->id]);

        return $order;
    }

    /** Pedido de la sesión, solo si es de este usuario y sigue sin pagarse. */
    public function reusableOrderFor(User $user): ?Order
    {
        $orderId = session(self::ORDER_SESSION_KEY);
        $order = $orderId ? Order::find($orderId) : null;

        if (! $order || $order->user_id !== $user->id || $order->isPaid()) {
            return null;
        }

        return $order;
    }

    /** Cierra el checkout en curso: el pedido ya se pagó o se abandonó. */
    public function forgetSession(): void
    {
        session()->forget([self::ORDER_SESSION_KEY, self::SHIPPING_SESSION_KEY]);
    }

    /** Descarta las claves vacías para que no pisen un valor bueno al fusionar. */
    private function filled(array $data): array
    {
        return array_filter($data, fn ($v) => trim((string) $v) !== '');
    }
}
