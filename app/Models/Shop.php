<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'address',
        'id_front_image',
        'id_back_image',
        'status',
    ];

    // Relación: la tienda pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Estado legible
    public function statusLabel(): string
    {
        return match ($this->status) {
            0 => 'Pending',
            1 => 'Approved',
            2 => 'Rejected',
            default => 'Unknown',
        };
    }
}