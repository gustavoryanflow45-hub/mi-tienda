<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\Rules\Password;

/**
 * Cuenta del panel de administración (guard 'admin', tabla admins).
 *
 * No es un User: un comprador o vendedor no puede convertirse en admin
 * cambiando una columna, y los tokens o sesiones de la tienda nunca
 * autentican aquí. Las cuentas se crean y cambian de contraseña solo desde
 * la consola (admin:create, admin:password).
 */
class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Política de contraseñas del panel: más estricta que la de la tienda,
     * porque una cuenta admin mueve dinero (liquidaciones, recargas).
     */
    public static function passwordRule(): Password
    {
        return Password::min(12)->letters()->mixedCase()->numbers()->symbols();
    }

    public function twoFactor(): HasOne
    {
        return $this->hasOne(AdminTwoFactor::class);
    }
}
