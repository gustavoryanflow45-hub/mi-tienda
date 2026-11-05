<?php

namespace Tests\Feature;

use App\Jobs\SendPushNotification;
use App\Models\Admin;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\OrderArrivedWarehouseNotification;
use App\Notifications\OrderStatusUpdatedNotification;
use App\Notifications\ShopStatusUpdatedNotification;
use App\Services\Push\FcmClient;
use App\Support\ApiAbilities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\PersonalAccessToken;
use RuntimeException;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

/**
 * Push con FCM: el canal encola un job por dispositivo, el cliente firma el
 * JWT de la cuenta de servicio, cachea el token OAuth, manda el mensaje a la
 * API HTTP v1 y olvida los tokens que FCM da por muertos. Google se simula
 * con Http::fake y una clave RSA generada para el test.
 */
class FcmPushTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    private const OAUTH = 'https://oauth2.googleapis.com/token';

    private const SEND = 'https://fcm.googleapis.com/v1/projects/mi-tienda-test/messages:send';

    private string $credentialsPath;

    private string $publicKey;

    protected function setUp(): void
    {
        parent::setUp();

        // openssl_pkey_new() necesita un openssl.cnf y en Windows/XAMPP PHP no
        // lo encuentra solo: se le da uno mínimo (firmar no lo necesita).
        $config = tempnam(sys_get_temp_dir(), 'ossl');
        file_put_contents($config, "[req]\ndistinguished_name = dn\n[dn]\n");
        $options = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'config' => $config];

        $key = openssl_pkey_new($options);
        openssl_pkey_export($key, $privatePem, null, $options);
        @unlink($config);
        $this->publicKey = openssl_pkey_get_details($key)['key'];

        $this->credentialsPath = tempnam(sys_get_temp_dir(), 'fcm').'.json';
        file_put_contents($this->credentialsPath, json_encode([
            'type' => 'service_account',
            'project_id' => 'mi-tienda-test',
            'private_key' => $privatePem,
            'client_email' => 'push@mi-tienda-test.iam.gserviceaccount.com',
            'token_uri' => self::OAUTH,
        ]));

        config(['services.fcm.credentials' => $this->credentialsPath, 'services.fcm.enabled' => true]);
    }

    protected function tearDown(): void
    {
        @unlink($this->credentialsPath);
        parent::tearDown();
    }

    protected function fakeGoogle(array $sendResponse = ['name' => 'projects/mi-tienda-test/messages/1'], int $status = 200): void
    {
        Http::fake([
            self::OAUTH => Http::response(['access_token' => 'ya29.test', 'expires_in' => 3599, 'token_type' => 'Bearer']),
            self::SEND => Http::response($sendResponse, $status),
        ]);
    }

    /** Un usuario con la app abierta en un dispositivo con ese token FCM. */
    protected function userWithDevice(string $fcm = 'fcm-token-dispositivo-0001-abcdefghijkl'): User
    {
        $user = $this->makeCustomer();
        $user->createToken('Pixel', ApiAbilities::for($user), now()->addDay())
            ->accessToken->forceFill(['push_token' => $fcm, 'push_platform' => 'android'])->save();

        return $user;
    }

    // ── El canal ─────────────────────────────────────────────────

    public function test_a_notification_is_pushed_to_each_device_with_its_type_and_id(): void
    {
        $this->fakeGoogle();
        $buyer = $this->userWithDevice();
        $order = $this->makePendingOrder($buyer, $this->makeSeller());

        $buyer->notify(new OrderStatusUpdatedNotification($order, 'confirmed'));

        $notificationId = $buyer->notifications()->value('id');

        Http::assertSent(function (HttpRequest $request) use ($order, $notificationId) {
            if ($request->url() !== self::SEND) {
                return false;
            }

            $message = $request['message'];

            return $request->hasHeader('Authorization', 'Bearer ya29.test')
                && $message['token'] === 'fcm-token-dispositivo-0001-abcdefghijkl'
                && $message['notification']['title'] === 'Tu pedido #'.$order->code
                && str_contains($message['notification']['body'], $order->code)
                // FCM solo admite strings en data.
                && $message['data'] === [
                    'type' => 'order_status_updated',
                    'notification_id' => $notificationId,
                    'order_id' => (string) $order->id,
                    'order_code' => $order->code,
                    'status' => 'confirmed',
                ]
                && $message['android']['priority'] === 'high';
        });
    }

    public function test_the_service_account_jwt_is_signed_and_the_oauth_token_is_cached(): void
    {
        $this->fakeGoogle();
        $buyer = $this->userWithDevice();
        $order = $this->makePendingOrder($buyer, $this->makeSeller());

        $buyer->notify(new OrderStatusUpdatedNotification($order, 'confirmed'));
        $buyer->notify(new OrderStatusUpdatedNotification($order, 'warehouse'));

        $oauth = Http::recorded(fn (HttpRequest $request) => $request->url() === self::OAUTH);
        $this->assertCount(1, $oauth, 'El token OAuth se pide una vez y se reutiliza.');
        $this->assertCount(2, Http::recorded(fn (HttpRequest $request) => $request->url() === self::SEND));

        // El JWT va firmado con la clave de la cuenta de servicio.
        [$jwtRequest] = $oauth->first();
        $this->assertSame('urn:ietf:params:oauth:grant-type:jwt-bearer', $jwtRequest['grant_type']);
        [$header, $claims, $signature] = explode('.', $jwtRequest['assertion']);
        $decode = fn (string $part) => base64_decode(strtr($part, '-_', '+/'));

        $this->assertSame(1, openssl_verify("{$header}.{$claims}", $decode($signature), $this->publicKey, OPENSSL_ALGO_SHA256));
        $payload = json_decode($decode($claims), true);
        $this->assertSame('push@mi-tienda-test.iam.gserviceaccount.com', $payload['iss']);
        $this->assertSame(FcmClient::SCOPE, $payload['scope']);
        $this->assertSame(self::OAUTH, $payload['aud']);
    }

    public function test_nothing_is_queued_without_credentials_or_devices(): void
    {
        Queue::fake();
        $buyer = $this->userWithDevice();
        $order = $this->makePendingOrder($buyer, $this->makeSeller());

        // Sin dispositivos con token: nada.
        $this->makeCustomer()->notify(new OrderStatusUpdatedNotification($order, 'confirmed'));
        Queue::assertNothingPushed();

        // Sin credenciales (o FCM_ENABLED=false): nada, y el aviso de la base sí se guarda.
        // El ChannelManager reutiliza la instancia del canal (y su cliente):
        // un cambio de config en caliente necesita olvidarla, como un
        // queue:restart en producción.
        config(['services.fcm.credentials' => null]);
        app(ChannelManager::class)->forgetDrivers();
        $buyer->notify(new OrderStatusUpdatedNotification($order, 'confirmed'));
        Queue::assertNothingPushed();
        $this->assertSame(1, $buyer->notifications()->count());

        config(['services.fcm.credentials' => $this->credentialsPath, 'services.fcm.enabled' => false]);
        app(ChannelManager::class)->forgetDrivers();
        $buyer->notify(new OrderStatusUpdatedNotification($order, 'warehouse'));
        Queue::assertNothingPushed();
    }

    public function test_one_job_per_device_on_the_queue(): void
    {
        Queue::fake();
        $buyer = $this->userWithDevice('fcm-token-movil-aaaaaaaaaaaaaaaaaaaa');
        $buyer->createToken('iPad', ApiAbilities::for($buyer))
            ->accessToken->forceFill(['push_token' => 'fcm-token-tablet-bbbbbbbbbbbbbbbbbb', 'push_platform' => 'ios'])->save();
        // Una sesión caducada no recibe push.
        $buyer->createToken('Viejo', ApiAbilities::for($buyer), now()->subDay())
            ->accessToken->forceFill(['push_token' => 'fcm-token-caducado-cccccccccccccccc'])->save();

        $buyer->notify(new OrderStatusUpdatedNotification($this->makePendingOrder($buyer, $this->makeSeller()), 'confirmed'));

        Queue::assertPushed(SendPushNotification::class, 2);
        Queue::assertPushed(SendPushNotification::class, fn ($job) => $job->token === 'fcm-token-tablet-bbbbbbbbbbbbbbbbbb');
        Queue::assertNotPushed(SendPushNotification::class, fn ($job) => $job->token === 'fcm-token-caducado-cccccccccccccccc');
    }

    public function test_push_text_uses_the_store_default_language(): void
    {
        Queue::fake();
        $seller = $this->makeSeller();
        $seller->createToken('Pixel', ApiAbilities::for($seller))
            ->accessToken->forceFill(['push_token' => 'fcm-token-vendedor-dddddddddddddddd'])->save();

        // El admin tiene el panel en inglés; el push al vendedor sale en español.
        app()->setLocale('en');
        $seller->notify(new ShopStatusUpdatedNotification($seller->shop, Shop::APPROVED));

        Queue::assertPushed(SendPushNotification::class, fn ($job) => $job->title === 'Tu tienda fue aprobada'
            && $job->data['type'] === 'shop_status_updated'
            && $job->data['approved'] === '1');
        $this->assertSame('en', app()->getLocale());
    }

    public function test_panel_accounts_never_get_push(): void
    {
        Queue::fake();
        $admin = Admin::factory()->create();

        $admin->notify(new OrderArrivedWarehouseNotification($this->makePendingOrder($this->makeCustomer(), $this->makeSeller()), 'Tienda'));

        Queue::assertNothingPushed();
    }

    // ── Errores de FCM ───────────────────────────────────────────

    public function test_an_unregistered_token_is_forgotten(): void
    {
        $this->fakeGoogle(['error' => [
            'code' => 404, 'status' => 'NOT_FOUND', 'message' => 'Requested entity was not found.',
            'details' => [['@type' => 'type.googleapis.com/google.firebase.fcm.v1.FcmError', 'errorCode' => 'UNREGISTERED']],
        ]], 404);
        $buyer = $this->userWithDevice();

        $buyer->notify(new OrderStatusUpdatedNotification($this->makePendingOrder($buyer, $this->makeSeller()), 'confirmed'));

        $this->assertSame(0, PersonalAccessToken::whereNotNull('push_token')->count());
        $this->assertSame([], $buyer->routeNotificationForFcm());
        // La sesión sigue abierta: solo se olvida el token push.
        $this->assertSame(1, $buyer->tokens()->count());
    }

    public function test_a_bad_message_does_not_delete_the_token(): void
    {
        $this->fakeGoogle(['error' => ['code' => 400, 'status' => 'INVALID_ARGUMENT', 'message' => 'Invalid JSON payload received.']], 400);
        $this->userWithDevice();

        try {
            (new SendPushNotification('fcm-token-dispositivo-0001-abcdefghijkl', 'T', 'B'))->handle(app(FcmClient::class));
            $this->fail('Debió lanzar para que la cola lo registre.');
        } catch (RuntimeException) {
        }

        $this->assertSame(1, PersonalAccessToken::whereNotNull('push_token')->count());
    }

    public function test_a_transient_error_is_retried_by_the_queue(): void
    {
        $this->fakeGoogle(['error' => ['code' => 503, 'status' => 'UNAVAILABLE']], 503);

        $job = new SendPushNotification('fcm-token-dispositivo-0001-abcdefghijkl', 'T', 'B');
        $this->assertSame(3, $job->tries);

        $this->expectException(RuntimeException::class);
        $job->handle(app(FcmClient::class));
    }

    public function test_an_expired_oauth_token_is_renewed_once(): void
    {
        Http::fake([
            self::OAUTH => Http::sequence()
                ->push(['access_token' => 'ya29.viejo', 'expires_in' => 3599])
                ->push(['access_token' => 'ya29.nuevo', 'expires_in' => 3599]),
            self::SEND => Http::sequence()
                ->push(['error' => ['code' => 401, 'status' => 'UNAUTHENTICATED']], 401)
                ->push(['name' => 'ok']),
        ]);

        $result = app(FcmClient::class)->send('fcm-token-dispositivo-0001-abcdefghijkl', new \App\Services\Push\FcmMessage('T', 'B'));

        $this->assertSame(FcmClient::SENT, $result);
        Http::assertSent(fn (HttpRequest $request) => $request->url() === self::SEND && $request->hasHeader('Authorization', 'Bearer ya29.nuevo'));
    }
}
