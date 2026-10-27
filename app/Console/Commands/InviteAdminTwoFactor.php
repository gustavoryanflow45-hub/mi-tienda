<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

/**
 * Genera el enlace firmado para que un admin inscriba su autenticador.
 *
 * Es la única forma de dar de alta el 2FA: hace falta acceso al servidor
 * para pedir el enlace y la contraseña del admin para usarlo. Con --reset
 * borra la inscripción anterior (móvil perdido o APP_KEY rotada).
 */
class InviteAdminTwoFactor extends Command
{
    protected $signature = 'admin:2fa-invite
                            {email : Correo de la cuenta admin}
                            {--reset : Borra la inscripción actual antes de generar el enlace}
                            {--minutes=30 : Validez del enlace}';

    protected $description = 'Genera el enlace firmado para inscribir el 2FA de un admin';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->where('user_type', 'admin')->first();

        if (! $user) {
            $this->error('No hay ningún admin con ese correo.');

            return self::FAILURE;
        }

        $current = $user->twoFactor;

        if ($current?->isConfirmed() && ! $this->option('reset')) {
            $this->error('Esa cuenta ya tiene 2FA. Usa --reset para reemplazarlo (invalida su autenticador y sus códigos de recuperación).');

            return self::FAILURE;
        }

        if ($current) {
            $current->delete();
            $this->warn('Inscripción anterior eliminada.');
        }

        $minutes = max(1, (int) $this->option('minutes'));

        $url = URL::temporarySignedRoute(
            'admin.two-factor.setup',
            now()->addMinutes($minutes),
            ['user' => $user->id],
        );

        $this->info("Enlace de alta para {$user->email} (válido {$minutes} min):");
        $this->line($url);
        $this->line('Entrégalo por un canal distinto del correo. El admin debe iniciar sesión en el panel y abrirlo.');

        return self::SUCCESS;
    }
}
