<?php

namespace App\Notifications\Channels;

use App\Jobs\SendPushNotification;
use App\Services\Push\FcmClient;
use App\Services\Push\FcmMessage;
use App\Support\NotificationTypes;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Traits\Localizable;

/**
 * Canal 'fcm' (FcmChannel::class en via()): push a los dispositivos con la
 * sesión de la app abierta (User::routeNotificationForFcm()). No envía en la
 * petición: encola un SendPushNotification por token. Sin credenciales de
 * Firebase o sin dispositivos registrados no hace nada, así que añadirlo a
 * un aviso nunca rompe el flujo que lo dispara.
 *
 * Al mensaje de toFcm() le añade `type` (NotificationTypes, el mismo de GET
 * /api/v1/notifications) y `notification_id` (el id de su fila en la base,
 * para que la app lo marque como leído al abrirlo).
 */
class FcmChannel
{
    use Localizable;

    public function __construct(private FcmClient $fcm) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFcm') || ! $this->fcm->configured()) {
            return;
        }

        $tokens = (array) $notifiable->routeNotificationFor('fcm', $notification);

        if ($tokens === []) {
            return;
        }

        // En el idioma por defecto de la tienda (services.fcm.locale), no en el de
        // la petición que dispara el aviso (el admin puede tener el panel en
        // inglés); los usuarios no guardan un idioma propio.
        /** @var FcmMessage $message */
        $message = $this->withLocale(config('services.fcm.locale', 'es'), fn () => $notification->toFcm($notifiable))->withData([
            'type' => NotificationTypes::for($notification::class),
            'notification_id' => (string) $notification->id,
        ]);

        foreach (array_unique($tokens) as $token) {
            SendPushNotification::dispatch($token, $message->title, $message->body, $message->stringData());
        }
    }
}
