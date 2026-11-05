<?php

namespace App\Support;

use App\Notifications\OrderStatusUpdatedNotification;
use App\Notifications\SellerNewOrderNotification;
use App\Notifications\SellerSettledNotification;
use App\Notifications\ShopStatusUpdatedNotification;

/**
 * Clave estable de cada aviso para las apps: la misma en GET
 * /api/v1/notifications (`type`) y en el `data.type` de los push, para que la
 * app elija icono y pantalla igual venga de donde venga.
 */
class NotificationTypes
{
    public const MAP = [
        OrderStatusUpdatedNotification::class => 'order_status_updated',
        SellerNewOrderNotification::class => 'seller_new_order',
        SellerSettledNotification::class => 'seller_settled',
        ShopStatusUpdatedNotification::class => 'shop_status_updated',
    ];

    public static function for(string $class): string
    {
        return self::MAP[$class] ?? 'other';
    }
}
