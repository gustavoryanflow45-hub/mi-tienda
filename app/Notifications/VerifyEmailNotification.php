<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    /** Minutos que dura el enlace antes de caducar. */
    public const EXPIRES_MINUTES = 60;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verifica tu correo electrónico')
            ->greeting("¡Hola, {$notifiable->name}!")
            ->line('Confirma tu correo electrónico para activar tu cuenta.')
            ->action('Verificar mi correo', $this->verificationUrl($notifiable))
            ->line('El enlace caduca en '.self::EXPIRES_MINUTES.' minutos.')
            ->line('Si no creaste esta cuenta, puedes ignorar este mensaje.');
    }

    /**
     * Enlace firmado y temporal: no hace falta guardar tokens en la base,
     * y la firma impide que nadie verifique la cuenta de otro.
     */
    public function verificationUrl(object $notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(self::EXPIRES_MINUTES),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->email),
            ],
        );
    }
}
