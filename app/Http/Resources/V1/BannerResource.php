<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Banner */
class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'image_url' => uploaded_asset($this->image),
            // Enlace de la web (puede ser una URL de producto o categoría que
            // la app traduzca a su propia pantalla).
            'link' => $this->link,
        ];
    }
}
