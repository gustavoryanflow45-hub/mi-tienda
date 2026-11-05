<?php

namespace App\Services;

use App\Models\Address;
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

    /** Reglas de los datos de envío: el checkout web y la API piden lo mismo. */
    public const SHIPPING_RULES = [
        'full_name' => ['required', 'string', 'max:255'],
        'phone' => ['required', 'string', 'max:30'],
        'email' => ['required', 'email', 'max:255'],
        'address' => ['required', 'string', 'max:255'],
        'city' => ['required', 'string', 'max:100'],
        'state' => ['nullable', 'string', 'max:100'],
        'country' => ['nullable', 'string', 'max:100'],
        'postal_code' => ['nullable', 'string', 'max:20'],
    ];

    /** Ítems del carrito del usuario, con su producto cargado. */
    public function items(User $user): Collection
    {
        return Cart::where('user_id', $user->id)->with('product.category')->get();
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
        return $this->shippingFrom($user, (array) session(self::SHIPPING_SESSION_KEY, []));
    }

    /**
     * Pedido pendiente listo para cobrar, creado a partir del carrito actual
     * (checkout web). Reutiliza el de la sesión si sigue sin pagarse, para no
     * acumular uno por cada intento de pago. Devuelve null si el carrito está
     * vacío.
     */
    public function pendingOrderFor(User $user): ?Order
    {
        $order = $this->syncPendingOrder(
            $user,
            $this->reusableOrderFor($user),
            $this->shippingSnapshot($user),
            session()->getId(),
        );

        if ($order) {
            session([self::ORDER_SESSION_KEY => $order->id]);
        }

        return $order;
    }

    /**
     * Crea el pedido pendiente a partir del carrito, o pone al día $reuse
     * (totales, líneas y envío) si sigue siendo del usuario y sin pagar. Es
     * el núcleo de pendingOrderFor() sin sesión: la API dice qué pedido
     * reutilizar y con qué datos de envío. Null si el carrito está vacío.
     */
    public function syncPendingOrder(User $user, ?Order $reuse, array $shipping, ?string $sessionId = null): ?Order
    {
        $items = $this->items($user);

        if ($items->isEmpty()) {
            return null;
        }

        $totals = $this->totals($items);
        $order = $reuse && $reuse->user_id === $user->id && ! $reuse->isPaid() ? $reuse : null;

        if ($order) {
            $order->update([
                'subtotal' => $totals['subtotal'],
                'shipping_total' => $totals['shipping'],
                'tax_amount' => $totals['tax'],
                'grand_total' => $totals['grand_total'],
                'shipping_address' => $shipping,
            ]);
            $order->orderDetails()->delete();
        } else {
            $order = Order::create([
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'code' => 'ORD-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -6)),
                'status' => 'pendiente',
                'payment_status' => 'unpaid',
                'delivery_status' => 'pending',
                'subtotal' => $totals['subtotal'],
                'shipping_total' => $totals['shipping'],
                'tax_amount' => $totals['tax'],
                'grand_total' => $totals['grand_total'],
                'shipping_address' => $shipping,
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

        return $order;
    }

    /** Datos de envío del perfil con lo que el comprador acaba de escribir encima. */
    public function shippingFrom(User $user, array $input): array
    {
        return array_merge($user->shippingSnapshot(), $this->filled($input));
    }

    /** Guarda los datos de envío como dirección por defecto para futuras compras. */
    public function rememberShipping(User $user, array $data): void
    {
        Address::updateOrCreate(['user_id' => $user->id, 'is_default' => 1], $data);
    }

    /**
     * El pago quedó confirmado por la pasarela: marca el pedido pagado
     * (Order::markPaid, que no hace nada si ya lo estaba) y vacía el carrito
     * del comprador. Lo llaman el webhook y la vuelta de Stripe, la vuelta de
     * PayPhone y la API.
     */
    public function completePayment(Order $order, string $gateway, string $reference): void
    {
        $order->markPaid($gateway, $reference);

        Cart::query()
            ->when(
                $order->user_id,
                fn ($q) => $q->where('user_id', $order->user_id),
                fn ($q) => $q->where('session_id', $order->session_id),
            )
            ->delete();
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
