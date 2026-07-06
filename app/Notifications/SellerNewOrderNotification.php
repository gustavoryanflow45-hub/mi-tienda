<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class SellerNewOrderNotification extends Notification
{
    public function __construct(public readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id'   => $this->order->id,
            'order_code' => $this->order->code,
            'total'      => $this->order->grand_total,
            'message'    => "Nuevo pedido #{$this->order->code} recibido.",
        ];
    }
}
