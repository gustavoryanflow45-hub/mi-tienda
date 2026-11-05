<?php

namespace App\Http\Requests\Api\V1;

use App\Services\CheckoutService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /api/v1/checkout/orders: los mismos datos de envío que el checkout
 * web (CheckoutService::SHIPPING_RULES) y, opcional, el pedido pendiente a
 * reutilizar — la API no tiene sesión donde recordarlo.
 */
class CheckoutOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return CheckoutService::SHIPPING_RULES + [
            'order_id' => ['nullable', 'integer'],
        ];
    }

    /** Solo los campos de envío. */
    public function shipping(): array
    {
        return collect($this->validated())->only(array_keys(CheckoutService::SHIPPING_RULES))->all();
    }
}
