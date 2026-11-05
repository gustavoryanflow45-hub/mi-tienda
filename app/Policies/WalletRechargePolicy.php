<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\User;
use App\Models\WalletRecharge;
use Illuminate\Auth\Access\Response;

/**
 * Una recarga de la billetera es de quien la pidió (user_id): solo esa persona la ve
 * desde la tienda o la API — a cualquier otro usuario se le responde 404,
 * sin confirmar que existe — y las cuentas del panel con wallet.view.
 * Pedirla es de cualquier usuario de la tienda; aprobarla o rechazarla es
 * solo del panel (permission:wallet.decide en sus rutas), nunca de la API.
 */
class WalletRechargePolicy
{
    public function view(User|Admin $actor, WalletRecharge $model): Response
    {
        if ($actor instanceof Admin) {
            return $actor->checkPermissionTo('wallet.view') ? Response::allow() : Response::deny();
        }

        return (int) $model->user_id === (int) $actor->id ? Response::allow() : Response::denyAsNotFound();
    }

    public function create(User|Admin $actor): bool
    {
        return $actor instanceof User;
    }
}
