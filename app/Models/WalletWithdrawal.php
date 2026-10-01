<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletWithdrawal extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
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
}