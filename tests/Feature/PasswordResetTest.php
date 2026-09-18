<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Recuperación de contraseña de punta a punta: pedir el enlace, abrirlo,
 * guardar la nueva clave y quedar dentro.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_form_renders_for_guests(): void
    {
        $this->get('/password/reset')
            ->assertOk()
            ->assertSee('Restablecer contraseña');
    }

    public function test_requesting_a_link_emails_the_user_in_spanish(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/password/email', ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status', 'Si existe una cuenta con ese correo, recibirás el enlace en breve.');

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($n) use ($user) {
            $mail = $n->toMail($user);

            return $mail->subject === 'Restablece tu contraseña'
                && str_contains($mail->actionUrl, '/password/reset/'.$n->token)
                && str_contains($mail->actionUrl, 'email='.urlencode($user->email));
        });
    }

    /** La respuesta no distingue cuentas existentes: evita enumerar usuarios. */
    public function test_unknown_email_gets_the_same_answer_and_no_mail(): void
    {
        Notification::fake();

        $this->post('/password/email', ['email' => 'nadie@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status', 'Si existe una cuenta con ese correo, recibirás el enlace en breve.')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_full_reset_flow_changes_password_and_logs_in(): void
    {
        $user = User::factory()->create(['password' => 'vieja-clave-123']);
        $token = Password::createToken($user);

        $this->get('/password/reset/'.$token.'?email='.urlencode($user->email))
            ->assertOk()
            ->assertSee('Elige una nueva contraseña')
            ->assertSee($user->email);

        $this->post('/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'nueva-clave-456',
            'password_confirmation' => 'nueva-clave-456',
        ])
            ->assertRedirect('/dashboard')
            ->assertSessionHas('success');

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('nueva-clave-456', $user->fresh()->password));
        $this->assertFalse(Hash::check('vieja-clave-123', $user->fresh()->password));

        // El token es de un solo uso.
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_invalid_token_is_rejected_with_a_readable_message(): void
    {
        $user = User::factory()->create(['password' => 'vieja-clave-123']);

        $this->post('/password/reset', [
            'token' => 'token-inventado',
            'email' => $user->email,
            'password' => 'nueva-clave-456',
            'password_confirmation' => 'nueva-clave-456',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors(['email' => 'Este enlace no es válido o ya caducó. Pide uno nuevo.']);

        $this->assertGuest();
        $this->assertTrue(Hash::check('vieja-clave-123', $user->fresh()->password));
    }

    public function test_short_or_mismatched_password_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post('/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'corta',
            'password_confirmation' => 'corta',
        ])->assertSessionHasErrors('password');

        $this->post('/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'nueva-clave-456',
            'password_confirmation' => 'otra-cosa-456',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_logged_in_users_are_sent_away_from_the_reset_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/password/reset')->assertRedirect();
    }
}
