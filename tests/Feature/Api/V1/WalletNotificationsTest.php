<?php

namespace Tests\Feature\Api\V1;

use App\Models\SellerSettlement;
use App\Models\User;
use App\Models\WalletRecharge;
use App\Models\WalletWithdrawal;
use App\Notifications\OrderStatusUpdatedNotification;
use App\Support\ApiAbilities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

/**
 * /api/v1/wallet, /api/v1/notifications y /api/v1/auth/push-token: la
 * billetera con la misma lógica que la web (WalletService), protegida
 * contra el doble envío (Idempotency-Key) y con policies; los avisos de la
 * base del propio usuario; y el registro del token FCM por dispositivo.
 */
class WalletNotificationsTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    protected array $bank = ['full_name' => 'Ana Pérez', 'bank_name' => 'Banco Pichincha', 'account_number' => '2200123456'];

    protected function api(User $user, ?array $abilities = null): static
    {
        return $this->bearer($user->createToken('test', $abilities ?? ApiAbilities::for($user), now()->addDay())->plainTextToken);
    }

    /**
     * Peticiones con ese token y ninguna otra cabecera de una petición
     * anterior (las del cliente de pruebas persisten: una Idempotency-Key
     * vieja se colaría en la siguiente).
     */
    protected function bearer(string $token): static
    {
        // El guard de Sanctum recuerda al usuario entre peticiones del mismo test.
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withHeader('Authorization', 'Bearer '.$token);
    }

    /** Cliente con una Idempotency-Key nueva (un envío de formulario). */
    protected function submit(User $user, ?string $key = null): static
    {
        return $this->api($user)->withHeader('Idempotency-Key', $key ?? (string) Str::uuid());
    }

    protected function proof(): UploadedFile
    {
        return UploadedFile::fake()->image('comprobante.jpg');
    }

    // ── Recargas ─────────────────────────────────────────────────

    public function test_a_recharge_request_keeps_its_network_and_transaction_and_stays_pending(): void
    {
        Storage::fake('public');
        $user = $this->makeCustomer();

        $id = $this->submit($user)->post('/api/v1/wallet/recharges', [
            'amount' => 50, 'network' => 'TRC20', 'transaction_id' => 'tx-abc-123', 'payment_proof' => $this->proof(),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.network', 'TRC20')
            ->assertJsonPath('data.transaction_id', 'tx-abc-123')
            ->assertJsonPath('data.has_proof', true)
            ->json('data.id');

        // Antes de este cambio WalletRecharge::$fillable no los tenía y se perdían.
        $recharge = WalletRecharge::findOrFail($id);
        $this->assertSame('TRC20', $recharge->network);
        $this->assertSame('tx-abc-123', $recharge->transaction_id);
        Storage::disk('public')->assertExists($recharge->payment_proof);

        // El saldo no cambia hasta que el panel la apruebe; su estado se consulta.
        $this->assertSame(0.0, (float) $user->refresh()->balance);
        $this->api($user)->getJson("/api/v1/wallet/recharges/{$id}")->assertOk()->assertJsonPath('data.status', 'pending');
    }

    public function test_a_recharge_needs_a_valid_proof(): void
    {
        Storage::fake('public');
        $user = $this->makeCustomer();
        $base = ['amount' => 50, 'network' => 'TRC20', 'transaction_id' => 'tx'];

        // Sin comprobante, con un tipo no admitido, demasiado grande o una red inventada.
        foreach ([
            [$base, 'payment_proof'],
            [$base + ['payment_proof' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')], 'payment_proof'],
            [$base + ['payment_proof' => UploadedFile::fake()->create('enorme.pdf', config('images.max_upload_kb') + 1, 'application/pdf')], 'payment_proof'],
            [['network' => 'BTC'] + $base + ['payment_proof' => $this->proof()], 'network'],
        ] as [$input, $field]) {
            $this->submit($user)->post('/api/v1/wallet/recharges', $input, ['Accept' => 'application/json'])
                ->assertStatus(422)
                ->assertJsonValidationErrors($field);
        }

        // Un PDF sí vale.
        $this->submit($user)->post('/api/v1/wallet/recharges', $base + [
            'payment_proof' => UploadedFile::fake()->create('comprobante.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertCreated();

        $this->assertSame(1, WalletRecharge::where('user_id', $user->id)->count());
    }

    // ── Retiros ──────────────────────────────────────────────────

    public function test_withdrawals_check_minimum_balance_and_bank_data(): void
    {
        config(['wallet.min_withdrawal' => 10]);
        $user = $this->makeCustomer();
        $user->forceFill(['balance' => 100])->save();

        $this->api($user)->getJson('/api/v1/wallet')->assertJsonPath('data.min_withdrawal', 10);

        foreach ([
            [['amount' => 5] + $this->bank, 'amount'],        // bajo el mínimo
            [['amount' => 500] + $this->bank, 'amount'],      // más que el saldo
            [['amount' => 20, 'account_number' => '12'] + $this->bank, 'account_number'],
            [['amount' => 20, 'account_number' => '<script>'] + $this->bank, 'account_number'],
            [['amount' => 20, 'bank_name' => ''] + $this->bank, 'bank_name'],
        ] as [$input, $field]) {
            $this->submit($user)->postJson('/api/v1/wallet/withdrawals', $input)
                ->assertStatus(422)
                ->assertJsonValidationErrors($field);
        }

        $id = $this->submit($user)->postJson('/api/v1/wallet/withdrawals', ['amount' => 20] + $this->bank)
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.account_last4', '3456')
            ->assertJsonMissingPath('data.account_number')
            ->json('data.id');

        $this->assertSame(80.0, (float) $user->refresh()->balance);
        $this->api($user)->getJson("/api/v1/wallet/withdrawals/{$id}")->assertOk()->assertJsonPath('data.status', 'pending');
    }

    public function test_every_withdrawal_waits_for_the_admin_even_if_settlements_cover_it(): void
    {
        $seller = $this->makeSeller();
        $seller->forceFill(['balance' => 100])->save();
        SellerSettlement::create([
            'seller_id' => $seller->id, 'total_sales' => 80, 'commission_rate' => 0.25,
            'commission' => 20, 'net_amount' => 60, 'lines_count' => 2, 'settled_at' => now(),
        ]);

        $this->api($seller)->getJson('/api/v1/wallet')->assertJsonMissingPath('data.withdrawable_now');

        // Lo cubren ventas liquidadas: igual pendiente; la marca solo informa al admin.
        $this->submit($seller)->postJson('/api/v1/wallet/withdrawals', ['amount' => 60] + $this->bank)
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.from_settlement', true)
            ->assertJsonPath('message', fn ($message) => filled($message));

        $this->assertSame(40.0, (float) $seller->refresh()->balance);
        $this->api($seller)->getJson('/api/v1/wallet/settlements')->assertOk()->assertJsonPath('data.0.net_amount', 60);
    }

    // ── Comisión de red de la app ────────────────────────────────

    public function test_an_app_recharge_credits_the_amount_minus_the_network_fee_once_approved(): void
    {
        Storage::fake('public');
        config(['wallet.app_network_fee' => 1]);
        $user = $this->makeCustomer();

        $this->api($user)->getJson('/api/v1/wallet')->assertJsonPath('data.network_fee', 1);

        $id = $this->submit($user)->post('/api/v1/wallet/recharges', [
            'amount' => 50, 'network' => 'TRC20', 'transaction_id' => 'tx', 'payment_proof' => $this->proof(),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.fee', 1)
            ->assertJsonPath('data.net_amount', 49)
            ->json('data.id');

        // Nada hasta que el admin la aprueba; entonces, 50 − 1.
        $this->assertSame(0.0, (float) $user->refresh()->balance);
        app(\App\Services\WalletService::class)->approveRecharge(WalletRecharge::findOrFail($id), \App\Models\Admin::factory()->create());
        $this->assertSame(49.0, (float) $user->refresh()->balance);

        // El movimiento muestra lo acreditado.
        $this->api($user)->getJson('/api/v1/wallet/movements')->assertJsonPath('data.0.amount', 49);

        // Un monto que no supera la comisión no tiene sentido.
        $this->submit($user)->post('/api/v1/wallet/recharges', [
            'amount' => 1, 'network' => 'TRC20', 'transaction_id' => 'tx2', 'payment_proof' => $this->proof(),
        ], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonValidationErrors('amount');
    }

    public function test_an_app_withdrawal_deducts_the_amount_and_transfers_it_minus_the_fee(): void
    {
        config(['wallet.app_network_fee' => 1]);
        $user = $this->makeCustomer();
        $user->forceFill(['balance' => 100])->save();

        $id = $this->submit($user)->postJson('/api/v1/wallet/withdrawals', ['amount' => 30] + $this->bank)
            ->assertCreated()
            ->assertJsonPath('data.amount', 30)
            ->assertJsonPath('data.fee', 1)
            ->assertJsonPath('data.net_amount', 29)
            ->assertJsonPath('data.status', 'pending')
            ->json('data.id');

        // Se descuenta el monto completo; el admin transfiere 29.
        $this->assertSame(70.0, (float) $user->refresh()->balance);
        $this->assertSame(29.0, WalletWithdrawal::findOrFail($id)->netAmount());

        // Rechazado: vuelve entero (no hubo transferencia ni comisión).
        app(\App\Services\WalletService::class)->rejectWithdrawal(WalletWithdrawal::findOrFail($id), \App\Models\Admin::factory()->create());
        $this->assertSame(100.0, (float) $user->refresh()->balance);

        $this->submit($user)->postJson('/api/v1/wallet/withdrawals', ['amount' => 1] + $this->bank)
            ->assertStatus(422)
            ->assertJsonValidationErrors('amount');
    }

    public function test_the_web_charges_no_network_fee(): void
    {
        $user = $this->makeCustomer();
        $user->forceFill(['balance' => 100])->save();

        $this->actingAs($user)->post('/wallet/withdraw', ['amount' => 30] + $this->bank)->assertSessionHas('success');

        $this->assertDatabaseHas('wallet_withdrawals', ['user_id' => $user->id, 'amount' => 30, 'fee' => 0, 'status' => 'pending']);
    }

    // ── Doble envío ──────────────────────────────────────────────

    public function test_a_retried_withdrawal_with_the_same_key_is_not_charged_twice(): void
    {
        $user = $this->makeCustomer();
        $user->forceFill(['balance' => 100])->save();
        $key = (string) Str::uuid();

        $first = $this->submit($user, $key)->postJson('/api/v1/wallet/withdrawals', ['amount' => 40] + $this->bank)->assertCreated();

        // El reintento (timeout, doble toque) devuelve la misma respuesta sin ejecutar nada.
        $this->submit($user, $key)->postJson('/api/v1/wallet/withdrawals', ['amount' => 40] + $this->bank)
            ->assertCreated()
            ->assertHeader('Idempotent-Replayed', 'true')
            ->assertJsonPath('data.id', $first->json('data.id'));

        $this->assertSame(1, WalletWithdrawal::where('user_id', $user->id)->count());
        $this->assertSame(60.0, (float) $user->refresh()->balance);

        // La misma clave con otros datos es un error de la app.
        $this->submit($user, $key)->postJson('/api/v1/wallet/withdrawals', ['amount' => 10] + $this->bank)
            ->assertStatus(422)
            ->assertJsonPath('code', 'idempotency_key_reused');

        // Sin clave no se procesa.
        $this->api($user)->postJson('/api/v1/wallet/withdrawals', ['amount' => 10] + $this->bank)
            ->assertStatus(400)
            ->assertJsonPath('code', 'idempotency_key_required');

        // Otra clave sí es otra solicitud.
        $this->submit($user)->postJson('/api/v1/wallet/withdrawals', ['amount' => 10] + $this->bank)->assertCreated();
        $this->assertSame(2, WalletWithdrawal::where('user_id', $user->id)->count());
    }

    public function test_a_failed_request_can_be_retried_with_the_same_key(): void
    {
        $user = $this->makeCustomer();
        $key = (string) Str::uuid();

        // Sin saldo: 422, no se guarda.
        $this->submit($user, $key)->postJson('/api/v1/wallet/withdrawals', ['amount' => 10] + $this->bank)->assertStatus(422);

        $user->forceFill(['balance' => 50])->save();

        $this->submit($user, $key)->postJson('/api/v1/wallet/withdrawals', ['amount' => 10] + $this->bank)
            ->assertCreated()
            ->assertHeaderMissing('Idempotent-Replayed');
    }

    public function test_a_concurrent_request_with_the_same_key_waits_its_turn(): void
    {
        $user = $this->makeCustomer();
        $user->forceFill(['balance' => 50])->save();
        $key = (string) Str::uuid();

        // Otra petición con la misma clave tiene el lock (sigue procesándose).
        $lock = Cache::lock('idempotency:'.$user->id.':api.v1.wallet.withdrawals.store:'.$key.':lock', 30);
        $this->assertTrue($lock->get());

        $this->submit($user, $key)->postJson('/api/v1/wallet/withdrawals', ['amount' => 10] + $this->bank)
            ->assertStatus(409)
            ->assertJsonPath('code', 'request_in_progress');

        $lock->release();
        $this->assertSame(0, WalletWithdrawal::where('user_id', $user->id)->count());
    }

    public function test_keys_are_per_user(): void
    {
        $key = (string) Str::uuid();
        $ana = $this->makeCustomer();
        $ana->forceFill(['balance' => 50])->save();
        $luis = $this->makeCustomer();
        $luis->forceFill(['balance' => 50])->save();

        $this->submit($ana, $key)->postJson('/api/v1/wallet/withdrawals', ['amount' => 10] + $this->bank)->assertCreated();
        // La misma clave de otro usuario no le devuelve la respuesta de Ana.
        $this->submit($luis, $key)->postJson('/api/v1/wallet/withdrawals', ['amount' => 10] + $this->bank)
            ->assertCreated()
            ->assertHeaderMissing('Idempotent-Replayed');

        $this->assertSame(1, WalletWithdrawal::where('user_id', $luis->id)->count());
    }

    // ── Acceso ───────────────────────────────────────────────────

    public function test_each_user_sees_only_their_own_requests(): void
    {
        $owner = $this->makeCustomer();
        $recharge = WalletRecharge::create(['user_id' => $owner->id, 'amount' => 20, 'network' => 'BEP20', 'transaction_id' => 't1', 'approval' => 0]);
        $withdrawal = WalletWithdrawal::create(['user_id' => $owner->id, 'amount' => 5, 'full_name' => 'X', 'bank_name' => 'B', 'account_number' => '1234', 'status' => 'pending']);

        $other = $this->makeCustomer();
        $this->api($other)->getJson('/api/v1/wallet/recharges')->assertOk()->assertJsonCount(0, 'data');
        $this->api($other)->getJson('/api/v1/wallet/withdrawals')->assertOk()->assertJsonCount(0, 'data');
        $this->api($other)->getJson('/api/v1/wallet/movements')->assertOk()->assertJsonCount(0, 'data');
        // Policies: la solicitud de otro es 404, no 403.
        $this->api($other)->getJson("/api/v1/wallet/recharges/{$recharge->id}")->assertNotFound();
        $this->api($other)->getJson("/api/v1/wallet/withdrawals/{$withdrawal->id}")->assertNotFound();

        $this->api($owner)->getJson('/api/v1/wallet')
            ->assertJsonPath('data.pending_recharges', 1)
            ->assertJsonPath('data.pending_withdrawals', 1);

        $this->api($owner, ['wallet:read'])->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/wallet/withdrawals', ['amount' => 1])->assertForbidden();
        $this->api($owner, ['profile:read'])->getJson('/api/v1/wallet')->assertForbidden();
    }

    public function test_there_is_no_way_to_approve_or_reject_from_the_api(): void
    {
        $names = collect($this->app['router']->getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/'))
            ->map(fn ($route) => $route->getName().' '.$route->uri());

        $this->assertEmpty($names->filter(fn ($line) => preg_match('/approve|reject|decide/i', $line))->all());
    }

    public function test_movements_list_everything_newest_first_with_signed_amounts(): void
    {
        $seller = $this->makeSeller();
        WalletRecharge::create(['user_id' => $seller->id, 'amount' => 20, 'approval' => 1])->forceFill(['created_at' => now()->subDays(3)])->save();
        WalletWithdrawal::create(['user_id' => $seller->id, 'amount' => 5, 'full_name' => 'X', 'bank_name' => 'B', 'account_number' => '1234', 'status' => 'rejected'])
            ->forceFill(['created_at' => now()->subDays(2)])->save();
        SellerSettlement::create([
            'seller_id' => $seller->id, 'total_sales' => 40, 'commission_rate' => 0.25,
            'commission' => 10, 'net_amount' => 30, 'lines_count' => 1, 'settled_at' => now()->subDay(),
        ]);

        $this->api($seller)->getJson('/api/v1/wallet/movements')
            ->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.type', 'settlement')
            ->assertJsonPath('data.0.amount', 30)
            ->assertJsonPath('data.1.type', 'withdrawal')
            ->assertJsonPath('data.1.amount', -5)
            ->assertJsonPath('data.1.status', 'rejected')
            ->assertJsonPath('data.2.type', 'recharge')
            ->assertJsonPath('data.2.status', 'approved');
    }

    public function test_wallet_requests_are_throttled(): void
    {
        $user = $this->makeCustomer();

        for ($i = 0; $i < 10; $i++) {
            $this->submit($user)->postJson('/api/v1/wallet/withdrawals', ['amount' => 1] + $this->bank)->assertStatus(422);
        }

        $this->submit($user)->postJson('/api/v1/wallet/withdrawals', ['amount' => 1] + $this->bank)
            ->assertStatus(429)
            ->assertJsonPath('code', 'too_many_requests');
    }

    // ── Notificaciones ───────────────────────────────────────────

    public function test_notifications_are_listed_counted_and_marked_read(): void
    {
        $buyer = $this->makeCustomer();
        $order = $this->makePendingOrder($buyer, $this->makeSeller());
        $buyer->notify(new OrderStatusUpdatedNotification($order, 'confirmed'));
        $buyer->notify(new OrderStatusUpdatedNotification($order, 'warehouse'));

        $this->api($buyer)->getJson('/api/v1/notifications/unread-count')->assertOk()->assertJsonPath('data.count', 2);

        $list = $this->api($buyer)->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.unread_count', 2)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.type', 'order_status_updated')
            ->assertJsonPath('data.0.payload.order_id', $order->id)
            ->assertJsonPath('data.0.read', false);

        // Listar no las marca como leídas.
        $this->api($buyer)->getJson('/api/v1/notifications/unread-count')->assertJsonPath('data.count', 2);

        $this->api($buyer)->postJson('/api/v1/notifications/'.$list->json('data.0.id').'/read')
            ->assertOk()
            ->assertJsonPath('data.read', true);
        $this->api($buyer)->getJson('/api/v1/notifications?unread=1')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.unread_count', 1);

        $this->api($buyer)->postJson('/api/v1/notifications/read-all')->assertNoContent();
        $this->api($buyer)->getJson('/api/v1/notifications/unread-count')->assertJsonPath('data.count', 0);
    }

    public function test_someone_elses_notification_is_not_found(): void
    {
        $owner = $this->makeCustomer();
        $owner->notify(new OrderStatusUpdatedNotification($this->makePendingOrder($owner, $this->makeSeller()), 'confirmed'));
        $id = $owner->notifications()->value('id');

        $this->api($this->makeCustomer())->postJson("/api/v1/notifications/{$id}/read")->assertNotFound();
        $this->assertNull($owner->notifications()->value('read_at'));
    }

    // ── Token push (FCM) ─────────────────────────────────────────

    public function test_a_device_registers_its_push_token_and_it_follows_the_session(): void
    {
        $user = $this->makeCustomer();
        $fcm = 'fcm-'.Str::random(40);
        // Una sesión por dispositivo: el mismo token en todas las peticiones de cada usuario.
        $userToken = $user->createToken('pixel', ApiAbilities::for($user))->plainTextToken;
        $other = $this->makeCustomer();
        $otherToken = $other->createToken('pixel', ApiAbilities::for($other))->plainTextToken;

        $this->bearer($userToken)->putJson('/api/v1/auth/push-token', ['token' => $fcm, 'platform' => 'android'])
            ->assertOk()
            ->assertJsonPath('data.push_enabled', true)
            ->assertJsonPath('data.push_platform', 'android')
            ->assertJsonMissingPath('data.push_token');

        $this->assertSame([$fcm], $user->routeNotificationForFcm());

        // Otra cuenta inicia sesión en el mismo móvil: el token pasa a ella.
        $this->bearer($otherToken)->putJson('/api/v1/auth/push-token', ['token' => $fcm, 'platform' => 'android'])->assertOk();
        $this->assertSame([], $user->routeNotificationForFcm());
        $this->assertSame([$fcm], $other->routeNotificationForFcm());

        // Desactivarlo deja la sesión abierta pero sin token.
        $this->bearer($otherToken)->deleteJson('/api/v1/auth/push-token')->assertNoContent();
        $this->assertSame([], $other->routeNotificationForFcm());

        $this->bearer($otherToken)->putJson('/api/v1/auth/push-token', ['token' => 'corto', 'platform' => 'web'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['token', 'platform']);
    }

    public function test_the_push_token_survives_rotation_and_dies_with_logout(): void
    {
        $user = $this->makeCustomer();
        $user->forceFill(['password' => bcrypt('Clave1234')])->save();
        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email, 'password' => 'Clave1234', 'device_id' => 'pixel', 'device_name' => 'Pixel',
        ])->assertOk();

        $this->bearer($login->json('data.token'))
            ->putJson('/api/v1/auth/push-token', ['token' => 'fcm-'.Str::random(40), 'platform' => 'ios'])
            ->assertOk();

        $rotated = $this->bearer($login->json('data.token'))->postJson('/api/v1/auth/token/rotate')->assertOk()->json('data.token');
        $this->assertCount(1, $user->routeNotificationForFcm());

        $this->bearer($rotated)->postJson('/api/v1/auth/logout')->assertNoContent();
        $this->assertSame([], $user->routeNotificationForFcm());
        $this->assertSame(0, PersonalAccessToken::whereNotNull('push_token')->count());
    }
}
