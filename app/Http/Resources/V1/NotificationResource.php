<?php

namespace App\Http\Resources\V1;

use App\Support\NotificationTypes;
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
    public function toArray(Request $request): array
    {
        $data = (array) $this->data;

        return [
            'id' => $this->id,
            'type' => NotificationTypes::for($this->type),
            'message' => isset($data['message']) ? (string) $data['message'] : null,
            'payload' => (object) Arr::except($data, 'message'),
            'read' => $this->read_at !== null,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
