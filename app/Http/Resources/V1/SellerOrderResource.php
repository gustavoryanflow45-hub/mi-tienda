<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Venta vista por un vendedor: solo SUS líneas del pedido (un pedido puede
 * mezclar varias tiendas) y los datos del comprador para el envío, como en
 * /seller/orders y el detalle web. Espera user y orderDetails (filtradas
 * por seller_id) con product.category cargados.
 *
 * @mixin \App\Models\Order
 */
class SellerOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lines = $this->orderDetails;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'payment_status' => $this->payment_status,
            'payment_status_label' => payment_status_label($this->payment_status),
            'delivery_status' => $this->delivery_status,
            'delivery_status_label' => delivery_status_label($this->delivery_status),
            // Lo que el vendedor puede hacer ahora mismo con el pedido.
            'actions' => [
                'confirm' => $this->payment_status === 'paid' && $this->delivery_status === 'pending',
                'send_to_warehouse' => $this->delivery_status === 'confirmed',
            ],
            'currency' => 'USD',
            'seller_total' => round($lines->sum(fn ($line) => (float) $line->price * $line->quantity), 2),
            'items' => OrderResource::items($lines),
            'buyer' => [
                'name' => $this->user?->name,
                'email' => $this->user?->email,
                'phone' => $this->user?->phone,
            ],
            'shipping_address' => (object) $this->shippingInfo(),
            'tracking' => OrderResource::tracking($this->resource),
            'created_at' => $this->created_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
        ];
    }
}
