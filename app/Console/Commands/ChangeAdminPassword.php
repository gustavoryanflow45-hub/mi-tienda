<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Services\AuditLog;
use Illuminate\Console\Command;

/**
 * Cambia la contraseña de una cuenta del panel. El panel no tiene
 * "olvidé mi contraseña": un enlace por correo sería una puerta más para
 * quien controle el buzón del admin.
 */
class ChangeAdminPassword extends Command
{
    protected $signature = 'admin:password {email : Correo de la cuenta admin}';

    protected $description = 'Cambia la contraseña de una cuenta del panel de administración';

    public function handle(AuditLog $audit): int
    {
        $admin = Admin::where('email', strtolower(trim($this->argument('email'))))->first();

        if (! $admin) {
            $this->error('No hay ningún admin con ese correo.');

            return self::FAILURE;
        }

        $password = CreateAdmin::askPassword($this);

        if ($password === null) {
            return self::FAILURE;
        }

        // Las sesiones abiertas con la contraseña anterior caen en su próxima
        // petición: las rutas del panel llevan 'auth.session'.
        $admin->forceFill(['password' => $password, 'remember_token' => null])->save();
        $audit->record('admin.password_changed', $admin, ['via' => 'console']);

        $this->info("Contraseña de {$admin->email} actualizada.");

        return self::SUCCESS;
    }
}
