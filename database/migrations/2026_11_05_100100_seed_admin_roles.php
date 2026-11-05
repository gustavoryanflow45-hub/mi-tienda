<?php

use App\Support\AdminPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Crea los permisos y roles del panel (App\Support\AdminPermissions) y hace
 * super-admin a cada cuenta que ya existía: hasta ahora todo admin podía
 * hacerlo todo, así que nadie pierde acceso al migrar. Las cuentas nuevas
 * reciben su rol con `admin:create --role=` o `admin:role`.
 *
 * down() quita las asignaciones y borra los roles y permisos del guard
 * 'admin'; las tablas las borra la migración anterior.
 */
return new class extends Migration
{
    public function up(): void
    {
        AdminPermissions::sync();

        $superAdmin = DB::table(config('permission.table_names.roles'))
            ->where('name', AdminPermissions::SUPER_ADMIN)
            ->where('guard_name', AdminPermissions::GUARD)
            ->value('id');

        foreach (DB::table('admins')->pluck('id') as $adminId) {
            DB::table(config('permission.table_names.model_has_roles'))->insertOrIgnore([
                'role_id' => $superAdmin,
                'model_type' => 'App\\Models\\Admin',
                config('permission.column_names.model_morph_key') => $adminId,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $tables = config('permission.table_names');
        $roleIds = DB::table($tables['roles'])->where('guard_name', AdminPermissions::GUARD)->pluck('id');
        $permissionIds = DB::table($tables['permissions'])->where('guard_name', AdminPermissions::GUARD)->pluck('id');

        DB::table($tables['model_has_roles'])->whereIn('role_id', $roleIds)->delete();
        DB::table($tables['model_has_permissions'])->whereIn('permission_id', $permissionIds)->delete();
        DB::table($tables['role_has_permissions'])->whereIn('role_id', $roleIds)->delete();
        DB::table($tables['roles'])->whereIn('id', $roleIds)->delete();
        DB::table($tables['permissions'])->whereIn('id', $permissionIds)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
