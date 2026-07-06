<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // ── Campos asignables masivamente ───────────────────────────
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'user_type',
        'email_verified',
        'verification_code',
        'balance',
        'referral_code',
        'referred_by',
        'banned',
    ];

    // ── Campos ocultos en serialización ─────────────────────────
    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
    ];

    // ── Casts ───────────────────────────────────────────────────
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'balance'           => 'decimal:2',
            'email_verified'    => 'boolean',
            'banned'            => 'boolean',
        ];
    }

    // ── Helpers ─────────────────────────────────────────────────

    /** ¿Es administrador? */
    public function isAdmin(): bool
    {
        return $this->user_type === 'admin';
    }

    /** ¿Es seller? */
    public function isSeller(): bool
    {
        return $this->user_type === 'seller';
    }

    /** ¿Está verificado? (email_verified_at O email_verified=1) */
    public function isVerified(): bool
    {
        return !is_null($this->email_verified_at) || (bool) $this->email_verified;
    }

    // ── Relaciones ───────────────────────────────────────────────

    /** Pedidos del usuario */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /** Lista de deseos */
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    /** Productos que ha agregado (seller) */
    public function products()
    {
        return $this->hasMany(Product::class, 'added_by');
    }

    /** Tienda del seller */
    public function shop()
    {
        return $this->hasOne(Shop::class);
    }

    /** Recargas de billetera */
    public function walletRecharges()
    {
        return $this->hasMany(WalletRecharge::class);
    }

    /** Retiros de billetera */
    public function walletWithdrawals()
    {
        return $this->hasMany(WalletWithdrawal::class);
    }

    /** Historial de movimientos de billetera */
    public function walletHistories()
    {
        return $this->hasMany(WalletHistory::class);
    }

    /** Reseñas escritas */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /** Direcciones guardadas */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    /** Usuario que lo refirió */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /** Usuarios referidos por este */
    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by');
    }
}
