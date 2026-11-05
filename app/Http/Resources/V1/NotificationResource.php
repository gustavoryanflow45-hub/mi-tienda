<?php

namespace App\Http\Resources\V1;

use App\Notifications\OrderStatusUpdatedNotification;
use App\Notifications\SellerNewOrderNotification;
use App\Notifications\SellerSettledNotification;
use App\Notifications\ShopStatusUpdatedNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

/**
 * Aviso de la base (tabla notifications). `type` es una clave estable para
 * que la app elija icono y pantalla; `payload` trae los ids (order_id,
 * shop_id...) para navegar. `message` es el texto guardado al crearse, en el
 * idioma por defecto de la tienda: la app puede mostrarlo tal cual o
 * componer el suyo con `type` y `payload`. (No se llama `data`: un
 * JsonResource con esa clave deja de envolverse en { "data": … }.)
 *
 * @mixin \Illuminate\Notifications\DatabaseNotification
 */
class NotificationResource extends JsonResource
{
    /** Clase de la notificación → clave para la app. */
    public const TYPES = [
        OrderStatusUpdatedNotification::class => 'order_status_updated',
        SellerNewOrderNotification::class => 'seller_new_order',
        SellerSettledNotification::class => 'seller_settled',
        ShopStatusUpdatedNotification::class => 'shop_status_updated',
    ];

    public function toArray(Request $request): array
    {
        $data = (array) $this->data;

        return [
            'id' => $this->id,
            'type' => self::TYPES[$this->type] ?? 'other',
            'message' => isset($data['message']) ? (string) $data['message'] : null,
            'payload' => (object) Arr::except($data, 'message'),
            'read' => $this->read_at !== null,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
