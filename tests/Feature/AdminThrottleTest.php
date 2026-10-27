<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\TwoFactorController;
use App\Models\AdminTwoFactor;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AdminThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $admin->forceFill(['password' => Hash::make('secreto123')])->save();

        return $admin;
    }

    protected function enroll(User $admin): string
    {
        $service = app(TwoFactorService::class);
        $secret = $service->generateSecret();
        $service->confirm($admin, $secret, app(Google2FA::class)->getCurrentOtp($secret));
        AdminTwoFactor::where('user_id', $admin->id)->update(['last_used_timestep' => null]);

        return $secret;
    }

    public function test_admin_login_is_throttled_after_five_attempts_even_with_the_right_password(): void
    {
        $admin = $this->makeAdmin();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['email' => $admin->email, 'password' => 'mala-'.$i]);
        }

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'secreto123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();

        // Pasado el minuto vuelve a poder entrar.
        $this->travel(61)->seconds();
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'secreto123'])
            ->assertRedirect(route('admin.home'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_the_storefront_password_reset_keeps_its_own_limit(): void
    {
        $route = $this->app['router']->getRoutes()->getByName('password.email');

        $this->assertContains('throttle:6,1', $route->gatherMiddleware());
    }

    public function test_two_factor_post_is_throttled_after_five_attempts(): void
    {
        $admin = $this->makeAdmin();
        $secret = $this->enroll($admin);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($admin)->post('/admin/two-factor', ['code' => '000000']);
        }

        $this->actingAs($admin)
            ->post('/admin/two-factor', ['code' => app(Google2FA::class)->getCurrentOtp($secret)])
            ->assertSessionHasErrors('code')
            ->assertSessionMissing(\App\Http\Middleware\EnsureAdminTwoFactor::SESSION_KEY);
    }

    public function test_ten_wrong_codes_sign_the_admin_out_and_lock_the_account(): void
    {
        $admin = $this->makeAdmin();
        $secret = $this->enroll($admin);

        for ($i = 1; $i <= TwoFactorController::MAX_FAILURES; $i++) {
            $response = $this->actingAs($admin)->post('/admin/two-factor', ['code' => '000000']);

            if ($i % 5 === 0) {
                $this->travel(61)->seconds(); // esquiva el límite por minuto
            }
        }

        $response->assertRedirect(route('admin.login'))->assertSessionHasErrors('email');
        $this->assertGuest();

        // Volver a iniciar sesión no reinicia la cuenta de fallos.
        $this->actingAs($admin)
            ->post('/admin/two-factor', ['code' => app(Google2FA::class)->getCurrentOtp($secret)])
            ->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }
}
