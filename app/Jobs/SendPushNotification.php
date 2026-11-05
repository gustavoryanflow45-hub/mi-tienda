<?php

namespace App\Jobs;

use App\Services\Push\FcmClient;
use App\Services\Push\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Un push a un dispositivo. Va en la cola para que la petición que provocó el
 * aviso (aprobar una tienda, confirmar un pedido…) no espere a Google, y uno
 * por token para que un reintento no repita el push a los que ya lo
 * recibieron. Si FCM dice que el token ya no vale, se borra de su sesión.
 */
class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    /** @var list<int> segundos entre reintentos (429/5xx de FCM). */
    public array $backoff = [10, 60, 300];

    public function __construct(
        public readonly string $token,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
    ) {}

    public function handle(FcmClient $fcm): void
    {
        if (! $fcm->configured()) {
            return;
        }

        $result = $fcm->send($this->token, new FcmMessage($this->title, $this->body, $this->data));

        if ($result === FcmClient::INVALID_TOKEN) {
            PersonalAccessToken::where('push_token', $this->token)
                ->update(['push_token' => null, 'push_platform' => null, 'push_token_updated_at' => null]);

            Log::info('FCM: token inválido olvidado', ['token' => FcmClient::mask($this->token)]);
        }
    }
}
