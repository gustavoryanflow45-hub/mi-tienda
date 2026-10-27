<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureAdminTwoFactor;
use App\Models\AdminTwoFactor;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use PragmaRX\Google2FA\Google2FA;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

class AdminTwoFactorTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        return User::factory()->create(['user_type' => 'admin']);
    }

    protected function otp(string $secret): string
    {
        return app(Google2FA::class)->getCurrentOtp($secret);
    }

    /** Inscribe al admin y devuelve [secreto, códigos de recuperación]. */
    protected function enroll(User $admin): array
    {
        $service = app(TwoFactorService::class);
        $secret = $service->generateSecret();
        $codes = $service->confirm($admin, $secret, $this->otp($secret));

        // El alta consume el código actual; se libera para poder probar el
        // desafío sin esperar al siguiente paso de 30 s.
        AdminTwoFactor::where('user_id', $admin->id)->update(['last_used_timestep' => null]);

        return [$secret, $codes];
    }

    protected function setupUrl(User $admin): string
    {
        return URL::temporarySignedRoute('admin.two-factor.setup', now()->addMinutes(30), ['user' => $admin->id]);
    }

    // ── Bloqueo del panel ────────────────────────────────────────

    public function test_every_panel_route_requires_the_second_factor(): void
    {
        $admin = $this->makeAdmin();

        $routes = collect($this->app['router']->getRoutes()->getRoutes())
            ->filter(fn (Route $route) => in_array('admin.2fa', $route->gatherMiddleware(), true));

        $this->assertNotEmpty($routes);

        foreach ($routes as $route) {
            $method = in_array('GET', $route->methods(), true) ? 'GET' : $route->methods()[0];
            $uri = '/'.preg_replace('/\{[^}]+\}/', '1', $route->uri());

            $this->actingAs($admin)->call($method, $uri)
                ->assertRedirect(route('admin.two-factor.challenge'));
        }
    }

    public function test_the_marker_of_another_account_does_not_open_the_panel(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeAdmin();

        $this->actingAs($admin)
            ->withSession([EnsureAdminTwoFactor::SESSION_KEY => $other->id])
            ->get('/admin/shops')
            ->assertRedirect(route('admin.two-factor.challenge'));
    }

    public function test_an_unenrolled_admin_is_told_how_to_get_an_enrollment_link(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/admin/two-factor')
            ->assertOk()
            ->assertSee('admin:2fa-invite')
            ->assertDontSee('name="code"', false);
    }

    // ── Alta ─────────────────────────────────────────────────────

    public function test_setup_is_not_reachable_without_the_signed_link(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get('/admin/two-factor/setup?user='.$admin->id)->assertForbidden();
    }

    public function test_a_signed_link_only_works_for_its_own_account(): void
    {
        $invited = $this->makeAdmin();

        $this->actingAs($this->makeAdmin())->get($this->setupUrl($invited))->assertForbidden();
    }

    public function test_admin_enrolls_with_the_signed_link_and_gets_hashed_recovery_codes(): void
    {
        $admin = $this->makeAdmin();
        $url = $this->setupUrl($admin);

        $this->actingAs($admin)->get($url)->assertOk()->assertSee('<svg', false);
        $secret = session(\App\Http\Controllers\Admin\TwoFactorController::SETUP_SESSION_KEY);
        $this->assertNotEmpty($secret);

        $response = $this->actingAs($admin)->post($url, ['code' => $this->otp($secret)])->assertOk();
        $response->assertSessionHas(EnsureAdminTwoFactor::SESSION_KEY, $admin->id);

        $record = $admin->fresh()->twoFactor;
        $this->assertTrue($record->isConfirmed());
        $this->assertCount(TwoFactorService::RECOVERY_CODES, $record->recovery_codes);

        // Los códigos se muestran en claro una vez, pero se guardan hasheados.
        $shown = $response->viewData('codes');
        foreach ($record->recovery_codes as $i => $hash) {
            $this->assertNotSame($shown[$i], $hash);
            $this->assertTrue(Hash::check($shown[$i], $hash));
        }

        // El secreto va cifrado en la columna.
        $raw = DB::table('admin_two_factors')->where('user_id', $admin->id)->value('secret');
        $this->assertStringNotContainsString($secret, $raw);

        $this->actingAs($admin)->get('/admin/shops')->assertOk();
    }

    public function test_enrollment_rejects_a_wrong_code(): void
    {
        $admin = $this->makeAdmin();
        $url = $this->setupUrl($admin);

        $this->actingAs($admin)->get($url);
        $this->actingAs($admin)->post($url, ['code' => '000000'])->assertSessionHasErrors('code');

        $this->assertNull($admin->fresh()->twoFactor);
    }

    // ── Desafío ──────────────────────────────────────────────────

    public function test_a_valid_code_opens_the_panel_and_cannot_be_replayed(): void
    {
        $admin = $this->makeAdmin();
        [$secret] = $this->enroll($admin);
        $code = $this->otp($secret);

        $this->actingAs($admin)
            ->post('/admin/two-factor', ['code' => $code])
            ->assertRedirect(route('admin.home'))
            ->assertSessionHas(EnsureAdminTwoFactor::SESSION_KEY, $admin->id);

        // Otra sesión intenta reutilizar el mismo código.
        $this->flushSession();
        $this->actingAs($admin)
            ->post('/admin/two-factor', ['code' => $code])
            ->assertSessionHasErrors('code')
            ->assertSessionMissing(EnsureAdminTwoFactor::SESSION_KEY);
    }

    public function test_a_wrong_code_keeps_the_panel_closed(): void
    {
        $admin = $this->makeAdmin();
        $this->enroll($admin);

        $this->actingAs($admin)
            ->post('/admin/two-factor', ['code' => '123456'])
            ->assertSessionHasErrors('code');

        $this->actingAs($admin)->get('/admin/shops')->assertRedirect(route('admin.two-factor.challenge'));
    }

    public function test_a_recovery_code_works_exactly_once(): void
    {
        $admin = $this->makeAdmin();
        [, $codes] = $this->enroll($admin);

        $this->actingAs($admin)
            ->post('/admin/two-factor', ['recovery_code' => strtolower($codes[3])])
            ->assertRedirect(route('admin.home'))
            ->assertSessionHas('warning');

        $this->assertCount(TwoFactorService::RECOVERY_CODES - 1, $admin->fresh()->twoFactor->recovery_codes);

        $this->flushSession();
        $this->actingAs($admin)
            ->post('/admin/two-factor', ['recovery_code' => $codes[3]])
            ->assertSessionHasErrors('code');
    }

    public function test_logout_drops_the_second_factor(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->withSession([EnsureAdminTwoFactor::SESSION_KEY => $admin->id])
            ->post('/admin/logout')
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
        $this->assertNull(session(EnsureAdminTwoFactor::SESSION_KEY));
    }

    // ── Comando de invitación ────────────────────────────────────

    public function test_invite_command_prints_a_signed_link_only_for_admins(): void
    {
        $admin = $this->makeAdmin();

        $this->artisan('admin:2fa-invite', ['email' => $this->makeSeller()->email])->assertFailed();

        $this->artisan('admin:2fa-invite', ['email' => $admin->email])
            ->expectsOutputToContain('/admin/two-factor/setup?')
            ->assertSuccessful();
    }

    public function test_invite_command_requires_reset_to_replace_an_enrollment(): void
    {
        $admin = $this->makeAdmin();
        $this->enroll($admin);

        $this->artisan('admin:2fa-invite', ['email' => $admin->email])->assertFailed();
        $this->assertNotNull($admin->fresh()->twoFactor);

        $this->artisan('admin:2fa-invite', ['email' => $admin->email, '--reset' => true])->assertSuccessful();
        $this->assertNull($admin->fresh()->twoFactor);
    }
}
