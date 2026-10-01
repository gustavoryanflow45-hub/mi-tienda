<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SellerSettlement extends Model
{
    protected $fillable = [
        'seller_id',
        'admin_id',
        'total_sales',
        'commission_rate',
        'commission',
        'net_amount',
        'lines_count',
        'settled_at',
    ];

    protected $casts = [
        'total_sales' => 'decimal:2',
        'commission_rate' => 'decimal:4',
        'commission' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'settled_at' => 'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'settlement_id');
    }
}
