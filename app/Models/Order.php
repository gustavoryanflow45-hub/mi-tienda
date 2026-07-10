<?php

namespace App\Models;

use App\Notifications\SellerNewOrderNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'code',
        'status',
        'payment_status',
        'delivery_status',
        'payment_gateway',
        'payment_reference',
        'subtotal',
        'tax_amount',
        'shipping_total',
        'discount_amount',
        'grand_total',
        'coupon_code',
        'shipping_address',
        'paid_at',
        'confirmed_at',
        'warehouse_at',
        'dispatched_at',
        'delivered_at',
        'dispatched_by',
    ];

    protected $casts = [
        'grand_total' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_address' => 'array',
        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'warehouse_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    // Alias used in some views
    public function items(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /** Snapshot de envío del pedido como arreglo (vacío si aún no se captura). */
    public function shippingInfo(): array
    {
        return is_array($this->shipping_address) ? $this->shipping_address : [];
    }

    /**
     * El almacén necesita nombre, teléfono, correo y dirección completos
     * para poder despachar el pedido.
     */
    public function hasCompleteShippingInfo(): bool
    {
        $info = $this->shippingInfo();

        foreach (['full_name', 'phone', 'email', 'address', 'city'] as $field) {
            if (trim((string) ($info[$field] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Mark order as paid, update order details, and notify sellers.
     * Safe to call multiple times — skips if already paid.
     */
    public function markPaid(string $gateway, string $reference): void
    {
        if ($this->isPaid()) {
            return;
        }

        $this->update([
            'status' => 'pagado',
            'payment_status' => 'paid',
            'payment_gateway' => $gateway,
            'payment_reference' => $reference,
            'paid_at' => now(),
        ]);

        $this->orderDetails()->update(['payment_status' => 'paid']);

        // Notify each distinct seller who has items in this order
        $sellerIds = $this->orderDetails()->whereNotNull('seller_id')->pluck('seller_id')->unique();

        foreach ($sellerIds as $sellerId) {
            $seller = User::find($sellerId);
            $seller?->notify(new SellerNewOrderNotification($this));
        }
    }
}
