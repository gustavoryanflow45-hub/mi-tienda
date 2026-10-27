<?php

namespace Tests\Feature;

use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Límites de los formularios públicos (AppServiceProvider::
 * configureAccountRateLimiting): vuelven atrás con el aviso en vez de una
 * página 429, sin devolver la contraseña al formulario.
 */
class AccountThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function assertThrottled($response, string $field = 'email'): void
    {
        $response->assertRedirect()
            ->assertSessionHasErrors($field)
            ->assertSessionMissing('_old_input.password');

        $this->assertStringContainsString(
            Str::before(__('Demasiados intentos. Espera :seconds segundos y vuelve a probar.'), ':seconds'),
            session('errors')->first($field),
        );
    }

    public function test_login_is_throttled_after_five_attempts_even_with_the_right_password(): void
    {
        $user = User::factory()->create(['user_type' => 'customer']);
        $user->forceFill(['password' => Hash::make('secreto123')])->save();

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'mala-'.$i]);
        }

        $this->assertThrottled(
            $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'secreto123'])
        );
        $this->assertGuest();

        // Otra cuenta desde la misma IP aún puede entrar (el tope por IP es 20).
        $other = User::factory()->create(['user_type' => 'customer']);
        $other->forceFill(['password' => Hash::make('secreto123')])->save();
        $this->post('/login', ['email' => $other->email, 'password' => 'secreto123']);
        $this->assertAuthenticatedAs($other);
    }

    public function test_register_is_throttled_per_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->from('/register')->post('/register', ['email' => "no-valido-{$i}"]);
        }

        $this->assertThrottled($this->from('/register')->post('/register', [
            'email' => 'nueva@example.com',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ]));
        $this->assertDatabaseMissing('users', ['email' => 'nueva@example.com']);

        // El tope por hora sigue contando pasado el minuto.
        for ($i = 0; $i < 3; $i++) {
            $this->travel(61)->seconds();
            for ($j = 0; $j < 5; $j++) {
                $this->post('/register', ['email' => 'x']);
            }
        }
        $this->travel(61)->seconds();
        $this->assertThrottled($this->from('/register')->post('/register', ['email' => 'otra@example.com']));
    }

    public function test_shop_registration_is_throttled_per_ip(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->from('/shops/create')->post('/shops/create', []);
        }

        $this->assertThrottled($this->from('/shops/create')->post('/shops/create', ['email' => 'tienda@example.com']));
        $this->assertDatabaseMissing('users', ['email' => 'tienda@example.com']);
    }

    public function test_newsletter_is_throttled_with_a_flash_message(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->from('/')->post('/subscribers', ['email' => "lector{$i}@example.com"])->assertSessionHas('success');
        }

        $this->from('/')->post('/subscribers', ['email' => 'spam@example.com'])
            ->assertRedirect('/')
            ->assertSessionHas('error');
        $this->assertFalse(Subscriber::where('email', 'spam@example.com')->exists());

        $this->postJson('/subscribers', ['email' => 'spam2@example.com'])
            ->assertStatus(429)
            ->assertJsonPath('status', 'error');
    }
}
