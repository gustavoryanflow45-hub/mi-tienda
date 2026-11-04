<?php

namespace App\Console\Commands;

use App\Support\AdminPermissions;
use Illuminate\Console\Command;

/**
 * Aplica App\Support\AdminPermissions a la base: crea los permisos y roles
 * que falten y deja cada rol con exactamente sus permisos. Idempotente.
 * Ejecútalo tras cambiar esa clase (la migración lo hizo la primera vez).
 */
class SyncAdminRoles extends Command
{
    protected $signature = 'admin:roles-sync';

    protected $description = 'Sincroniza los roles y permisos del panel con App\\Support\\AdminPermissions';

    public function handle(): int
    {
        AdminPermissions::sync();

        $this->info(count(AdminPermissions::PERMISSIONS).' permisos y '.count(AdminPermissions::roles()).' roles sincronizados.');

        return self::SUCCESS;
    }
}
