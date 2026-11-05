<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletWithdrawal extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        // Comisión de red (solo la app la cobra; 0 en la web).
        'fee',
        'full_name',
        'bank_name',
        'account_number',
        'status',
        'from_settlement',
    ];

    protected $casts = [
        'from_settlement' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Lo que el admin transfiere al aprobarlo: el monto menos la comisión de red. */
    public function netAmount(): float
    {
        return round((float) $this->amount - (float) $this->fee, 2);
    }
}
