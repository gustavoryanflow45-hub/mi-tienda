<?php

namespace App\Notifications;

use App\Models\Shop;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Avisa al vendedor cuando un admin aprueba o rechaza su tienda.
 *
 * Va también por correo porque la decisión puede tardar días: el vendedor
 * no tiene por qué estar dentro de la tienda cuando ocurre.
 */
class ShopStatusUpdatedNotification extends Notification
{
    public function __construct(
        public readonly Shop $shop,
        public readonly int $status,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
            'status' => $this->status,
            'approved' => $this->isApproved(),
            'message' => $this->message(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->isApproved()
                ? 'Tu tienda fue aprobada'
                : 'Novedades sobre tu solicitud de tienda')
            ->greeting("¡Hola, {$notifiable->name}!")
            ->line($this->message());

        return $this->isApproved()
            ? $mail->action('Ir a mi panel de vendedor', route('seller.products.index'))
                ->line('Ya puedes publicar productos y gestionar tus pedidos.')
            : $mail->line('Si crees que se trata de un error, responde a este correo o contacta con soporte.');
    }

    public function isApproved(): bool
    {
        return $this->status === 1;
    }

    protected function message(): string
    {
        return $this->isApproved()
            ? "¡Tu tienda «{$this->shop->name}» fue aprobada! Ya puedes publicar productos."
            : "Tu tienda «{$this->shop->name}» fue rechazada. Contacta con soporte para más información.";
    }
}
