<?php

namespace App\Notifications;

use App\Models\SellerSettlement;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Avisa al vendedor de que un admin liquidó sus ventas y el neto ya está
 * en su billetera. Va también por correo: la liquidación la decide el admin
 * cuando quiere, así que el vendedor no tiene por qué estar conectado.
 */
class SellerSettledNotification extends Notification
{
    public function __construct(public readonly SellerSettlement $settlement) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'settlement_id' => $this->settlement->id,
            'total_sales' => (float) $this->settlement->total_sales,
            'commission' => (float) $this->settlement->commission,
            'net_amount' => (float) $this->settlement->net_amount,
            'message' => $this->message(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $s = $this->settlement;

        return (new MailMessage)
            ->subject('Liquidación de ventas acreditada en tu billetera')
            ->greeting("¡Hola, {$notifiable->name}!")
            ->line($this->message())
            ->line(sprintf(
                'Ventas: $%s · Comisión (%d%%): -$%s · Acreditado: $%s.',
                number_format($s->total_sales, 2),
                round($s->commission_rate * 100),
                number_format($s->commission, 2),
                number_format($s->net_amount, 2),
            ))
            ->action('Ver mi billetera', route('wallet.index'))
            ->line('Desde allí puedes solicitar el retiro. Tus ventas y ganancias del panel empiezan un ciclo nuevo desde cero.');
    }

    protected function message(): string
    {
        return sprintf(
            'Se liquidaron tus ventas: $%s acreditados en tu billetera.',
            number_format($this->settlement->net_amount, 2),
        );
    }
}
