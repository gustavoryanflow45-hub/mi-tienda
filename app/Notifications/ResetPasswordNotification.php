<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Correo de restablecimiento de contraseña, en el idioma de la tienda.
 *
 * Hereda del de Laravel para reutilizar el token del broker (tabla
 * password_reset_tokens, caducidad en config/auth.php); solo cambia el
 * texto. User::sendPasswordResetNotification() es quien lo envía.
 */
class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable)
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Restablece tu contraseña')
            ->greeting("¡Hola, {$notifiable->name}!")
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta.')
            ->action('Restablecer contraseña', $this->resetUrl($notifiable))
            ->line("El enlace caduca en {$minutes} minutos.")
            ->line('Si no pediste este cambio, ignora este mensaje: tu contraseña sigue siendo la misma.');
    }

    protected function resetUrl($notifiable)
    {
        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}
