<?php

namespace App\Support;

use App\Models\Shop;
use App\Models\User;

/**
 * Habilidades (abilities) de los tokens de la API según el rol del usuario.
 *
 * Se calculan al emitir o rotar el token: si una tienda se aprueba después,
 * el vendedor las obtiene en su siguiente rotación. Aun así, los endpoints
 * de vendedor vuelven a comprobar la tienda en cada petición (un token
 * antiguo no debe sobrevivir a un rechazo).
 *
 * Nunca hay habilidades de administración: el panel no está en la API.
 */
class ApiAbilities
{
    public const CUSTOMER = [
        'profile:read',
        'profile:write',
        'cart:write',
        'checkout:write',
        'orders:read',
    ];

    public const SELLER = [
        'seller:products',
        'seller:orders',
    ];

    /** @return list<string> */
    public static function for(User $user): array
    {
        $abilities = self::CUSTOMER;

        if ($user->isSeller() && (int) $user->shop?->status === Shop::APPROVED) {
            $abilities = array_merge($abilities, self::SELLER);
        }

        return $abilities;
    }
}
