<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos y roles del panel (spatie/laravel-permission, guard 'admin').
 *
 * Es la única definición: la migración de roles, `admin:roles-sync` y los
 * tests la aplican con sync(), que es idempotente. Para añadir un permiso:
 * agrégalo aquí, dáselo a los roles que toque, protege la ruta con
 * 'permission:<nombre>' y ejecuta `php artisan admin:roles-sync`.
 *
 * super-admin recibe siempre TODOS los permisos (no hay un Gate::before que
 * lo salte todo): así se ve en la base qué puede hacer cada rol.
 */
class AdminPermissions
{
    public const GUARD = 'admin';

    public const SUPER_ADMIN = 'super-admin';

    /** permiso => descripción (para `admin:role --list`). */
    public const PERMISSIONS = [
        'shops.view' => 'Ver tiendas y su documentación',
        'shops.decide' => 'Aprobar o rechazar tiendas',
        'products.view' => 'Ver productos de cualquier vendedor',
        'products.edit' => 'Editar productos de cualquier vendedor',
        'products.moderate' => 'Aprobar o desactivar productos',
        'orders.view' => 'Ver cualquier pedido y los datos del comprador',
        'warehouse.view' => 'Ver el almacén',
        'warehouse.operate' => 'Despachar y entregar pedidos',
        'settlements.view' => 'Ver ventas pendientes y liquidaciones',
        'settlements.settle' => 'Liquidar ventas a un vendedor',
        'wallet.view' => 'Ver recargas y retiros',
        'wallet.decide' => 'Aprobar o rechazar recargas y retiros',
    ];

    /** rol => permisos. super-admin no se lista: recibe todos. */
    public const ROLES = [
        // Dinero: liquidaciones y billetera; consulta lo demás.
        'finanzas' => [
            'settlements.view', 'settlements.settle',
            'wallet.view', 'wallet.decide',
            'orders.view', 'shops.view', 'products.view',
        ],
        // Catálogo, tiendas y logística; no toca dinero.
        'operaciones' => [
            'shops.view', 'shops.decide',
            'products.view', 'products.edit', 'products.moderate',
            'orders.view',
            'warehouse.view', 'warehouse.operate',
        ],
        // Solo lectura: ve todo para atender consultas, no cambia nada.
        'soporte' => [
            'shops.view', 'products.view', 'orders.view',
            'warehouse.view', 'settlements.view', 'wallet.view',
        ],
    ];

    /** Todos los roles, super-admin primero. */
    public static function roles(): array
    {
        return array_merge([self::SUPER_ADMIN], array_keys(self::ROLES));
    }

    /**
     * Crea los permisos y roles que falten y deja a cada rol exactamente con
     * los permisos de arriba. No borra permisos ni roles que ya no estén
     * definidos (puede haber cuentas que los tengan): se quitan a mano.
     */
    public static function sync(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_keys(self::PERMISSIONS) as $name) {
            Permission::findOrCreate($name, self::GUARD);
        }

        Role::findOrCreate(self::SUPER_ADMIN, self::GUARD)->syncPermissions(array_keys(self::PERMISSIONS));

        foreach (self::ROLES as $role => $permissions) {
            Role::findOrCreate($role, self::GUARD)->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
