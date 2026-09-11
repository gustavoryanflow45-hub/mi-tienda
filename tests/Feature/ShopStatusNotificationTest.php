<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ShopStatusUpdatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

class ShopStatusNotificationTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        return User::factory()->create(['user_type' => 'admin']);
    }

    public function test_approving_notifies_the_seller(): void
    {
        Notification::fake();
        $seller = $this->makePendingSeller();

        $this->actingAs($this->makeAdmin())
            ->post("/admin/shops/{$seller->shop->id}/approve");

        Notification::assertSentTo(
            $seller,
            ShopStatusUpdatedNotification::class,
            fn ($n) => $n->isApproved()
                && in_array('database', $n->via($seller), true)
                && in_array('mail', $n->via($seller), true),
        );
    }

    public function test_rejecting_notifies_the_seller(): void
    {
        Notification::fake();
        $seller = $this->makeSeller();

        $this->actingAs($this->makeAdmin())
            ->post("/admin/shops/{$seller->shop->id}/reject");

        Notification::assertSentTo(
            $seller,
            ShopStatusUpdatedNotification::class,
            fn ($n) => ! $n->isApproved(),
        );
    }

    /** Reaprobar una tienda ya aprobada no debe volver a avisar. */
    public function test_no_notification_when_status_does_not_change(): void
    {
        Notification::fake();
        $seller = $this->makeSeller();

        $this->actingAs($this->makeAdmin())
            ->post("/admin/shops/{$seller->shop->id}/approve");

        Notification::assertNothingSentTo($seller);
    }

    public function test_seller_sees_the_banner_on_the_dashboard_once(): void
    {
        $seller = $this->makePendingSeller();
        $seller->email_verified_at = now();
        $seller->save();

        $this->actingAs($this->makeAdmin())
            ->post("/admin/shops/{$seller->shop->id}/approve");

        $this->assertSame(
            1,
            $seller->unreadNotifications()->where('type', ShopStatusUpdatedNotification::class)->count(),
        );

        // Primera visita: se ve el aviso
        $this->actingAs($seller->fresh())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('fue aprobada', false);

        // Queda marcado como leído
        $this->assertSame(
            0,
            $seller->unreadNotifications()->where('type', ShopStatusUpdatedNotification::class)->count(),
        );

        // Segunda visita: ya no aparece
        $this->actingAs($seller->fresh())
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('fue aprobada', false);
    }

    public function test_rejected_seller_sees_the_banner_on_the_simple_dashboard(): void
    {
        $seller = $this->makeSeller();
        $seller->email_verified_at = null;
        $seller->email_verified = 0;
        $seller->save();

        $this->actingAs($this->makeAdmin())
            ->post("/admin/shops/{$seller->shop->id}/reject");

        $this->actingAs($seller->fresh())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('fue rechazada', false);
    }

    public function test_mail_content_matches_the_decision(): void
    {
        $seller = $this->makePendingSeller();

        $approved = new ShopStatusUpdatedNotification($seller->shop, 1);
        $mail = $approved->toMail($seller)->render();
        $this->assertStringContainsString('aprobada', $mail);

        $rejected = new ShopStatusUpdatedNotification($seller->shop, 2);
        $this->assertStringContainsString('rechazada', $rejected->toMail($seller)->render());
    }
}
