<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\User;
use App\Models\WalletWithdrawal;
use Illuminate\Auth\Access\Response;

/**
 * Un retiro de la billetera es de quien lo pidió (user_id): solo esa persona lo ve
 * desde la tienda o la API — a cualquier otro usuario se le responde 404,
 * sin confirmar que existe — y las cuentas del panel con wallet.view.
 * Pedirlo es de cualquier usuario de la tienda; aprobarlo o rechazarlo es
 * solo del panel (permission:wallet.decide en sus rutas), nunca de la API.
 */
class WalletWithdrawalPolicy
{
    public function view(User|Admin $actor, WalletWithdrawal $model): Response
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
