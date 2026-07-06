<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

/**
 * Sent to the customer every time the delivery status of their
 * order changes, so they always know where their package is.
 */
class OrderStatusUpdatedNotification extends Notification
{
    public function __construct(
        public readonly Order $order,
        public readonly string $status,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_code' => $this->order->code,
            'status' => $this->status,
            'message' => $this->statusMessage(),
        ];
    }

    protected function statusMessage(): string
    {
        return match ($this->status) {
            'confirmed' => "Tu pedido #{$this->order->code} fue confirmado por el vendedor.",
            'warehouse' => "Tu pedido #{$this->order->code} llegó al almacén y está siendo preparado.",
            'on_the_way' => "¡Tu pedido #{$this->order->code} fue despachado y va en camino!",
            'delivered' => "Tu pedido #{$this->order->code} fue entregado. ¡Gracias por tu compra!",
            default => "Tu pedido #{$this->order->code} cambió de estado.",
        };
    }
}
