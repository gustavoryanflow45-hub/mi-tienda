<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDetail extends Model
{
    protected $fillable = [
        'order_id',
        'seller_id',
        'product_id',
        'variation',
        'product_name',
        'price',
        'quantity',
        'tax',
        'shipping_cost',
        'discount_on_product',
        'delivery_status',
        'payment_status',
        'reviewed',
    ];

    protected $casts = [
        'price'               => 'decimal:2',
        'tax'                 => 'decimal:2',
        'shipping_cost'       => 'decimal:2',
        'discount_on_product' => 'decimal:2',
        'reviewed'            => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
