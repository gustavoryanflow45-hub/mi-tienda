<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Services\AuditLog;
use App\Support\AdminPermissions;
use Illuminate\Console\Command;

/**
 * Cambia el rol de una cuenta del panel. Cada cuenta tiene un solo rol:
 * el nuevo reemplaza al anterior. No hay pantalla para esto a propósito:
 * dar poder en el panel exige acceso al servidor, como crear la cuenta.
 */
class AssignAdminRole extends Command
{
    protected $signature = 'admin:role
                            {email? : Correo de la cuenta admin}
                            {role? : super-admin, finanzas, operaciones o soporte}
                            {--list : Muestra los roles, sus permisos y quién tiene cada uno}';

    protected $description = 'Asigna el rol de una cuenta del panel (o los lista con --list)';

    public function handle(AuditLog $audit): int
    {
        if ($this->option('list') || ! $this->argument('email')) {
            return $this->list();
        }

        $admin = Admin::where('email', strtolower(trim($this->argument('email'))))->first();

        if (! $admin) {
            $this->error('No hay ningún admin con ese correo.');

            return self::FAILURE;
        }

        $role = $this->argument('role') ?: $this->choice('Rol', AdminPermissions::roles());

        if (! in_array($role, AdminPermissions::roles(), true)) {
            $this->error('Rol desconocido. Usa uno de: '.implode(', ', AdminPermissions::roles()).'.');

            return self::FAILURE;
        }

        $before = $admin->getRoleNames()->implode(',') ?: null;

        // No dejar el panel sin nadie que pueda dar roles.
        if ($before === AdminPermissions::SUPER_ADMIN && $role !== AdminPermissions::SUPER_ADMIN
            && Admin::role(AdminPermissions::SUPER_ADMIN)->count() === 1) {
            $this->error('Es el único super-admin: asigna super-admin a otra cuenta antes de cambiarle el rol.');

            return self::FAILURE;
        }

        $admin->syncRoles([$role]);
        $audit->record('admin.role_assigned', $admin, ['via' => 'console', 'role' => $role, 'previous' => $before]);

        $this->info("{$admin->email}: {$role}".($before ? " (antes: {$before})" : '').'.');

        return self::SUCCESS;
    }

    protected function list(): int
    {
        $rows = [];

        foreach (AdminPermissions::roles() as $role) {
            $permissions = $role === AdminPermissions::SUPER_ADMIN
                ? ['todos']
                : AdminPermissions::ROLES[$role];

            $rows[] = [
                $role,
                implode(', ', $permissions),
                Admin::role($role)->pluck('email')->implode(', ') ?: '—',
            ];
        }

        $this->table(['Rol', 'Permisos', 'Cuentas'], $rows);

        return self::SUCCESS;
    }
}
