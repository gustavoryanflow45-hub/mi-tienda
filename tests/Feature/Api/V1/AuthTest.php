<?php

namespace Tests\Feature\Api\V1;

use App\Models\Shop;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use App\Support\ApiAbilities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

/**
 * /api/v1/auth: alta, login por dispositivo, rotación, revocación, cuentas
 * sin verificar o bloqueadas, y el formato de errores de la API.
 */
class AuthTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    protected function customer(array $attributes = []): User
    {
        $user = User::factory()->create($attributes + ['user_type' => 'customer']);
        $user->forceFill(['password' => Hash::make('Clave1234')])->save();

        return $user;
    }

    protected function login(User $user, string $device = 'device-a', string $name = 'Pixel de prueba'): array
    {
        return $this->postJson('/api/v1/auth/login', [
            'email' => $user->email, 'password' => 'Clave1234',
            'device_id' => $device, 'device_name' => $name,
        ])->assertOk()->json('data');
    }

    protected function bearer(string $token): static
    {
        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    // ── Alta ─────────────────────────────────────────────────────

    public function test_register_creates_a_customer_sends_verification_and_gives_no_token(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Ana Pérez', 'email' => 'ANA@Tienda.test',
            'password' => 'Clave1234', 'password_confirmation' => 'Clave1234',
            // Intentos de escalar privilegios: se ignoran.
            'user_type' => 'seller', 'balance' => 9999,
        ])->assertCreated();

        $response->assertJsonPath('data.email', 'ana@tienda.test')
            ->assertJsonPath('data.type', 'customer')
            ->assertJsonPath('data.email_verified', false)
            ->assertJsonMissingPath('data.token');

        $user = User::where('email', 'ana@tienda.test')->firstOrFail();
        $this->assertSame('customer', $user->user_type);
        $this->assertEquals(0, (float) $user->balance);
        $this->assertSame(0, $user->tokens()->count());
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_register_validates_with_the_api_error_format(): void
    {
        $this->customer(['email' => 'dup@tienda.test']);

        $this->postJson('/api/v1/auth/register', [
            'name' => '', 'email' => 'dup@tienda.test', 'password' => 'corta', 'password_confirmation' => 'otra',
        ])->assertStatus(422)
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    // ── Login ────────────────────────────────────────────────────

    public function test_login_issues_an_expiring_device_token_with_customer_abilities(): void
    {
        $user = $this->customer();
        $data = $this->login($user);

        $this->assertSame('Bearer', $data['token_type']);
        $this->assertSame(ApiAbilities::CUSTOMER, $data['abilities']);
        $this->assertSame('device-a', $data['device']['device_id']);
        $this->assertSame($user->id, $data['user']['id']);

        $token = PersonalAccessToken::findToken($data['token']);
        $this->assertSame('device-a', $token->device_id);
        $this->assertTrue($token->expires_at->between(now()->addDays(29), now()->addDays(31)));
        // En la base solo queda el hash.
        $this->assertNotSame($data['token'], $token->token);

        $this->bearer($data['token'])->getJson('/api/v1/auth/me')
            ->assertOk()->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('meta.abilities', ApiAbilities::CUSTOMER);
    }

    public function test_wrong_credentials_get_the_same_answer_whether_the_email_exists_or_not(): void
    {
        $user = $this->customer();

        foreach ([$user->email, 'nadie@tienda.test'] as $email) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $email, 'password' => 'mala1234', 'device_id' => 'd', 'device_name' => 'x',
            ])->assertStatus(401)->assertExactJson([
                'message' => __('Correo o contraseña incorrectos.'),
                'code' => 'invalid_credentials',
            ]);
        }
    }

    public function test_unverified_and_banned_accounts_get_no_token(): void
    {
        $unverified = $this->customer(['email_verified_at' => null, 'email_verified' => 0]);
        $banned = $this->customer();
        $banned->forceFill(['banned' => true])->save();

        foreach ([[$unverified, 'email_not_verified'], [$banned, 'account_disabled']] as [$user, $code]) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $user->email, 'password' => 'Clave1234', 'device_id' => 'd', 'device_name' => 'x',
            ])->assertForbidden()->assertJsonPath('code', $code);

            $this->assertSame(0, $user->tokens()->count());
        }
    }

    public function test_an_approved_seller_gets_seller_abilities_and_a_pending_one_does_not(): void
    {
        $seller = $this->makeSeller();
        $seller->forceFill(['password' => Hash::make('Clave1234')])->save();
        $this->assertSame(array_merge(ApiAbilities::CUSTOMER, ApiAbilities::SELLER), $this->login($seller)['abilities']);

        $pending = $this->makePendingSeller();
        $pending->forceFill(['password' => Hash::make('Clave1234'), 'email_verified_at' => now()])->save();
        $this->assertSame(ApiAbilities::CUSTOMER, $this->login($pending)['abilities']);
    }

    // ── Dispositivos ─────────────────────────────────────────────

    public function test_logging_in_again_from_the_same_device_replaces_its_token(): void
    {
        $user = $this->customer();
        $first = $this->login($user, 'device-a')['token'];
        $second = $this->login($user, 'device-a')['token'];
        $this->login($user, 'device-b');

        $this->assertSame(2, $user->tokens()->count());
        $this->bearer($first)->getJson('/api/v1/auth/me')->assertUnauthorized()->assertJsonPath('code', 'unauthenticated');
        $this->app['auth']->forgetGuards();
        $this->bearer($second)->getJson('/api/v1/auth/me')->assertOk();
    }

    public function test_the_device_limit_closes_the_least_used_session(): void
    {
        config(['api.max_devices' => 2]);
        $user = $this->customer();

        $this->login($user, 'viejo');
        $this->login($user, 'medio');
        $this->login($user, 'nuevo');

        $this->assertEqualsCanonicalizing(['medio', 'nuevo'], $user->tokens()->pluck('device_id')->all());
    }

    public function test_devices_can_be_listed_and_revoked_one_by_one_or_all_at_once(): void
    {
        $user = $this->customer();
        $a = $this->login($user, 'device-a', 'Móvil')['token'];
        $b = $this->login($user, 'device-b', 'Tablet');
        $this->login($user, 'device-c', 'Otro');

        $list = $this->bearer($a)->getJson('/api/v1/auth/devices')->assertOk()->json('data');
        $this->assertCount(3, $list);
        $this->assertSame([true], collect($list)->where('device_id', 'device-a')->pluck('current')->all());
        $this->assertArrayNotHasKey('token', $list[0]);

        // Revocar la tablet desde el móvil.
        $this->bearer($a)->deleteJson('/api/v1/auth/devices/'.$b['device']['id'])->assertNoContent();
        $this->assertSame(2, $user->tokens()->count());

        // Un id ajeno es un 404.
        $other = $this->customer();
        $foreign = $this->login($other, 'x')['device']['id'];
        $this->bearer($a)->deleteJson('/api/v1/auth/devices/'.$foreign)->assertNotFound()->assertJsonPath('code', 'not_found');

        // Cerrar las demás: queda solo la actual.
        $this->bearer($a)->deleteJson('/api/v1/auth/devices')->assertNoContent();
        $this->assertSame(['device-a'], $user->tokens()->pluck('device_id')->all());

        $this->bearer($a)->deleteJson('/api/v1/auth/devices?include_current=1')->assertNoContent();
        $this->assertSame(0, $user->tokens()->count());
    }

    // ── Rotación, caducidad, cierre ──────────────────────────────

    public function test_rotation_issues_a_fresh_token_and_kills_the_old_one(): void
    {
        $user = $this->customer();
        $old = $this->login($user, 'device-a');

        $this->travel(20)->days();

        $new = $this->bearer($old['token'])->postJson('/api/v1/auth/token/rotate')->assertOk()->json('data');

        $this->assertNotSame($old['token'], $new['token']);
        $this->assertSame('device-a', $new['device']['device_id']);
        $this->assertTrue(PersonalAccessToken::findToken($new['token'])->expires_at->greaterThan(now()->addDays(29)));
        $this->assertNull(PersonalAccessToken::findToken($old['token']));
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_rotation_recalculates_abilities_after_a_shop_is_approved(): void
    {
        $seller = $this->makePendingSeller();
        $seller->forceFill(['password' => Hash::make('Clave1234'), 'email_verified_at' => now()])->save();
        $token = $this->login($seller)['token'];

        $seller->shop->update(['status' => Shop::APPROVED]);

        $abilities = $this->bearer($token)->postJson('/api/v1/auth/token/rotate')->json('data.abilities');
        $this->assertContains('seller:products', $abilities);
    }

    public function test_an_expired_token_is_rejected(): void
    {
        $token = $this->login($this->customer())['token'];

        $this->travel(31)->days();

        $this->bearer($token)->getJson('/api/v1/auth/me')->assertUnauthorized()->assertJsonPath('code', 'unauthenticated');
    }

    public function test_logout_revokes_only_the_current_device(): void
    {
        $user = $this->customer();
        $a = $this->login($user, 'device-a')['token'];
        $this->login($user, 'device-b');

        $this->bearer($a)->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->assertSame(['device-b'], $user->tokens()->pluck('device_id')->all());
    }

    public function test_a_token_dies_when_the_account_is_banned(): void
    {
        $user = $this->customer();
        $token = $this->login($user)['token'];

        $user->forceFill(['banned' => true])->save();

        $this->bearer($token)->getJson('/api/v1/auth/me')->assertForbidden()->assertJsonPath('code', 'account_disabled');
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_a_token_missing_the_ability_is_forbidden(): void
    {
        $user = $this->customer();
        $plain = $user->createToken('limitado', ['orders:read'], now()->addDay())->plainTextToken;

        $this->bearer($plain)->getJson('/api/v1/auth/me')->assertForbidden()->assertJsonPath('code', 'forbidden');
    }

    // ── Cuenta sin sesión ────────────────────────────────────────

    public function test_resend_and_forgot_answer_the_same_for_unknown_emails(): void
    {
        Notification::fake();
        $unverified = $this->customer(['email_verified_at' => null, 'email_verified' => 0]);

        $known = $this->postJson('/api/v1/auth/email/resend', ['email' => $unverified->email])->assertStatus(202)->json();
        $unknown = $this->postJson('/api/v1/auth/email/resend', ['email' => 'nadie@tienda.test'])->assertStatus(202)->json();
        $this->assertSame($known, $unknown);
        Notification::assertSentTo($unverified, VerifyEmailNotification::class);

        $verified = $this->customer();
        $known = $this->postJson('/api/v1/auth/password/forgot', ['email' => $verified->email])->assertStatus(202)->json();
        $unknown = $this->postJson('/api/v1/auth/password/forgot', ['email' => 'nadie@tienda.test'])->assertStatus(202)->json();
        $this->assertSame($known, $unknown);
        Notification::assertSentTo($verified, ResetPasswordNotification::class);
    }

    public function test_auth_endpoints_are_throttled(): void
    {
        $user = $this->customer();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'mala'.$i, 'device_id' => 'd', 'device_name' => 'x']);
        }

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'Clave1234', 'device_id' => 'd', 'device_name' => 'x'])
            ->assertStatus(429)
            ->assertJsonPath('code', 'too_many_requests')
            ->assertHeader('Retry-After');
    }

    // ── Formato e idioma ─────────────────────────────────────────

    public function test_errors_are_json_even_without_an_accept_header(): void
    {
        $this->get('/api/v1/auth/me')->assertUnauthorized()
            ->assertExactJson(['message' => __('No autenticado. Inicia sesión de nuevo.'), 'code' => 'unauthenticated']);

        $this->get('/api/v1/nada')->assertNotFound()->assertJsonPath('code', 'not_found');
        $this->get('/api/v1/auth/login')->assertStatus(405)->assertJsonPath('code', 'method_not_allowed');
    }

    public function test_messages_follow_accept_language(): void
    {
        $this->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertHeader('Content-Language', 'en')
            ->assertJsonPath('message', 'Unauthenticated. Please sign in again.');
    }

    public function test_a_web_session_does_not_authenticate_the_api(): void
    {
        $this->actingAs($this->customer())->getJson('/api/v1/auth/me')->assertUnauthorized();
    }
}
