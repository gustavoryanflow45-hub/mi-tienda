<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * /admin: lleva a la primera sección que el rol de la cuenta puede ver.
 * Antes iba siempre a tiendas, que finanzas o un rol nuevo podrían no ver.
 */
class HomeController extends Controller
{
    /** permiso => ruta, en el orden de la barra del panel. */
    public const SECTIONS = [
        'shops.view' => 'admin.shops',
        'products.view' => 'admin.products.index',
        'orders.view' => 'admin.orders.index',
        'settlements.view' => 'admin.settlements',
        'wallet.view' => 'admin.wallet',
        'warehouse.view' => 'admin.warehouse.index',
    ];

    public function __invoke(Request $request)
    {
        $admin = $request->user('admin');

        foreach (self::SECTIONS as $permission => $route) {
            if ($admin->checkPermissionTo($permission)) {
                return redirect()->route($route);
            }
        }

        abort(403, __('Tu cuenta no tiene ningún rol en el panel. Pide a un super-admin que te asigne uno.'));
    }
}
