<?php

namespace App\Http\Resources\V1;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Pedido del comprador. Los estados van como clave estable (para que la app
 * decida) y como texto traducido (para mostrarlo). Espera
 * orderDetails.product.category cargado.
 *
 * @mixin \App\Models\Order
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'payment_status' => $this->payment_status,
            'payment_status_label' => payment_status_label($this->payment_status),
            'payment_gateway' => $this->payment_gateway,
            'delivery_status' => $this->delivery_status,
            'delivery_status_label' => delivery_status_label($this->delivery_status),
            'currency' => 'USD',
            'totals' => [
                'subtotal' => round((float) $this->subtotal, 2),
                'shipping' => round((float) $this->shipping_total, 2),
                'tax' => round((float) $this->tax_amount, 2),
                'grand_total' => round((float) $this->grand_total, 2),
            ],
            'shipping_address' => (object) $this->shippingInfo(),
            'items' => self::items($this->orderDetails),
            // Fechas de cada paso de la entrega (null mientras no llega).
            'tracking' => self::tracking($this->resource),
            'created_at' => $this->created_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
        ];
    }

    /** Líneas del pedido; cada una con su variante legible. */
    public static function items(Collection $lines): Collection
    {
        return $lines->map(fn ($line) => [
            'id' => $line->id,
            'product_id' => $line->product_id,
            'name' => $line->product_name,
            'thumbnail_url' => uploaded_asset($line->product?->thumbnail),
            'variation' => (string) $line->variation,
            'variant' => CartItemResource::variant($line),
            'price' => round((float) $line->price, 2),
            'quantity' => (int) $line->quantity,
            'line_total' => round((float) $line->price * $line->quantity, 2),
            'shipping_cost' => round((float) $line->shipping_cost, 2),
        ])->values();
    }

    /** pending → confirmed → warehouse → on_the_way → delivered, con sus fechas. */
    public static function tracking(Order $order): array
    {
        return [
            'confirmed_at' => $order->confirmed_at?->toIso8601String(),
            'warehouse_at' => $order->warehouse_at?->toIso8601String(),
            'dispatched_at' => $order->dispatched_at?->toIso8601String(),
            'delivered_at' => $order->delivered_at?->toIso8601String(),
        ];
    }
}
