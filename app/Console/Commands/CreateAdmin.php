<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Services\AuditLog;
use App\Support\AdminPermissions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Crea una cuenta del panel (tabla admins). No hay registro web de admins:
 * hace falta acceso al servidor. Después, el admin necesita su enlace de
 * 2FA (admin:2fa-invite) para entrar.
 *
 * Nace con el rol más bajo (soporte, solo lectura) salvo que --role diga
 * otro: dar más poder debe ser una decisión explícita.
 */
class CreateAdmin extends Command
{
    protected $signature = 'admin:create
                            {email : Correo de la nueva cuenta admin}
                            {--name= : Nombre visible (por defecto, lo que va antes de la @)}
                            {--role=soporte : super-admin, finanzas, operaciones o soporte}';

    protected $description = 'Crea una cuenta del panel de administración';

    public function handle(AuditLog $audit): int
    {
        $email = strtolower(trim($this->argument('email')));
        $name = $this->option('name') ?: strstr($email, '@', true);

        $role = (string) $this->option('role');

        $validator = Validator::make(['email' => $email, 'name' => $name, 'role' => $role], [
            'email' => 'required|email|max:255|unique:admins,email',
            'name' => 'required|string|max:255',
            'role' => ['required', Rule::in(AdminPermissions::roles())],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        $password = $this->askPassword($this);

        if ($password === null) {
            return self::FAILURE;
        }

        $admin = Admin::create(['name' => $name, 'email' => $email, 'password' => $password]);
        $admin->assignRole($role);
        $audit->record('admin.created', $admin, ['via' => 'console', 'role' => $role]);

        $this->info("Admin {$admin->email} creado (id {$admin->id}, rol {$role}).");
        $this->line("Siguiente paso: php artisan admin:2fa-invite {$admin->email}");

        return self::SUCCESS;
    }

    /**
     * Pide la contraseña dos veces sin mostrarla y la valida con la política
     * del panel. Lo usa también admin:password.
     */
    public static function askPassword(Command $command): ?string
    {
        $password = (string) $command->secret('Contraseña (mín. 12, mayúsculas, minúsculas, números y símbolos)');
        $validator = Validator::make(['password' => $password], ['password' => ['required', Admin::passwordRule()]]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $command->error($error);
            }

            return null;
        }

        if ((string) $command->secret('Repite la contraseña') !== $password) {
            $command->error('Las contraseñas no coinciden.');

            return null;
        }

        return $password;
    }
}
