<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function unverifiedUser(): User
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $user->email_verified = 0;
        $user->save();

        return $user;
    }

    protected function signedUrlFor(User $user): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(VerifyEmailNotification::EXPIRES_MINUTES),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );
    }

    public function test_registration_sends_the_verification_email(): void
    {
        Notification::fake();

        $this->post('/register', [
            'first_name' => 'Nuevo',
            'last_name' => 'Cliente',
            'email' => 'nuevo@example.com',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
        ]);

        $user = User::where('email', 'nuevo@example.com')->first();

        $this->assertNotNull($user);
        $this->assertFalse($user->isVerified());
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_signed_link_verifies_the_account(): void
    {
        $user = $this->unverifiedUser();
        $this->assertFalse($user->isVerified());

        $this->get($this->signedUrlFor($user))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue($user->isVerified());
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue((bool) $user->email_verified);
    }

    /** El enlace llega por correo: debe funcionar sin sesión iniciada. */
    public function test_link_works_without_being_logged_in(): void
    {
        $user = $this->unverifiedUser();

        $this->assertGuest();
        $this->get($this->signedUrlFor($user));

        $this->assertTrue($user->fresh()->isVerified());
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_unsigned_link_is_rejected(): void
    {
        $user = $this->unverifiedUser();

        $this->get("/email/verify/{$user->id}/".sha1($user->email))
            ->assertForbidden();

        $this->assertFalse($user->fresh()->isVerified());
    }

    public function test_tampered_signature_is_rejected(): void
    {
        $user = $this->unverifiedUser();
        $url = $this->signedUrlFor($user);

        $this->get($url.'x')->assertForbidden();

        $this->assertFalse($user->fresh()->isVerified());
    }

    /** Nadie debe poder verificar la cuenta de otro cambiando el id. */
    public function test_cannot_verify_another_account_by_swapping_the_id(): void
    {
        $victim = $this->unverifiedUser();
        $attacker = $this->unverifiedUser();

        // Firma válida para el atacante, pero apuntando a la víctima.
        $url = str_replace(
            "/email/verify/{$attacker->id}/",
            "/email/verify/{$victim->id}/",
            $this->signedUrlFor($attacker),
        );

        $this->get($url)->assertForbidden();

        $this->assertFalse($victim->fresh()->isVerified());
    }

    public function test_expired_link_is_rejected(): void
    {
        $user = $this->unverifiedUser();
        $url = $this->signedUrlFor($user);

        $this->travel(VerifyEmailNotification::EXPIRES_MINUTES + 1)->minutes();

        $this->get($url)->assertForbidden();

        $this->assertFalse($user->fresh()->isVerified());
    }

    public function test_resend_sends_another_email(): void
    {
        Notification::fake();
        $user = $this->unverifiedUser();

        $this->actingAs($user)
            ->post('/email/resend')
            ->assertSessionHas('success');

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_resend_is_rate_limited(): void
    {
        Notification::fake();
        $user = $this->unverifiedUser();

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($user)->post('/email/resend');
        }

        $this->actingAs($user)
            ->post('/email/resend')
            ->assertStatus(429);
    }

    public function test_already_verified_user_is_sent_back(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_notice_page_requires_login(): void
    {
        $this->get(route('verification.notice'))->assertRedirect('/login');
    }
}
