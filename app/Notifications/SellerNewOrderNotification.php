<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use App\Services\Push\FcmMessage;
use Illuminate\Notifications\Notification;

class SellerNewOrderNotification extends Notification
{
    public function __construct(public readonly Order $order) {}

    public function via(object $notifiable): array
    {
        // Push a la app del vendedor (si tiene sesión abierta en ella).
        return $notifiable instanceof User ? ['database', FcmChannel::class] : ['database'];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return new FcmMessage(
            __('Nueva venta'),
            __('Nuevo pedido #:code recibido.', ['code' => $this->order->code]),
            ['order_id' => $this->order->id, 'order_code' => $this->order->code],
        );
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
