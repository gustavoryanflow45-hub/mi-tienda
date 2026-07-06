<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

/**
 * Sent to warehouse staff (admins) when a seller sends an order
 * to the warehouse. Feeds the "nuevas llegadas" badge in /warehouse.
 */
class OrderArrivedWarehouseNotification extends Notification
{
    public function __construct(
        public readonly Order $order,
        public readonly string $sellerName,
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
            'seller' => $this->sellerName,
            'total' => $this->order->grand_total,
            'message' => "El pedido #{$this->order->code} llegó al almacén y está listo para despacho.",
        ];
    }
}
