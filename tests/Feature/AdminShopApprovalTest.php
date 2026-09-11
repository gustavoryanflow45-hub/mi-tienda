<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

class AdminShopApprovalTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        return User::factory()->create(['user_type' => 'admin']);
    }

    // ── Acceso ───────────────────────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/shops')->assertRedirect('/login');
    }

    public function test_customer_cannot_open_the_screen(): void
    {
        $this->actingAs($this->makeCustomer())
            ->get('/admin/shops')
            ->assertForbidden();
    }

    /** Un vendedor no debe poder aprobarse a sí mismo. */
    public function test_seller_cannot_open_or_approve(): void
    {
        $seller = $this->makePendingSeller();

        $this->actingAs($seller)->get('/admin/shops')->assertForbidden();

        $this->actingAs($seller)
            ->post("/admin/shops/{$seller->shop->id}/approve")
            ->assertForbidden();

        $this->assertSame(0, (int) $seller->shop->fresh()->status);
    }

    public function test_admin_sees_pending_shops(): void
    {
        $seller = $this->makePendingSeller();

        $this->actingAs($this->makeAdmin())
            ->get('/admin/shops')
            ->assertOk()
            ->assertSee($seller->shop->name)
            ->assertSee('Pendiente');
    }

    // ── Aprobar / rechazar ───────────────────────────────────────

    public function test_admin_approves_and_seller_gets_in(): void
    {
        $seller = $this->makePendingSeller();
        $seller->email_verified_at = now();
        $seller->save();

        // Antes: bloqueado por shop.approved
        $this->actingAs($seller)->get('/seller/products')->assertRedirect(route('dashboard'));

        $this->actingAs($this->makeAdmin())
            ->post("/admin/shops/{$seller->shop->id}/approve")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(1, (int) $seller->shop->fresh()->status);

        // Después: entra
        $this->actingAs($seller->fresh())->get('/seller/products')->assertOk();
    }

    public function test_admin_rejects_and_seller_loses_access(): void
    {
        $seller = $this->makeSeller();          // aprobada de inicio
        $seller->email_verified_at = now();
        $seller->save();

        $this->actingAs($seller)->get('/seller/products')->assertOk();

        $this->actingAs($this->makeAdmin())
            ->post("/admin/shops/{$seller->shop->id}/reject")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(2, (int) $seller->shop->fresh()->status);

        $this->actingAs($seller->fresh())
            ->get('/seller/products')
            ->assertRedirect(route('dashboard'));
    }

    public function test_approving_twice_is_reported_not_duplicated(): void
    {
        $shop = $this->makeSeller()->shop;

        $this->actingAs($this->makeAdmin())
            ->post("/admin/shops/{$shop->id}/approve")
            ->assertSessionHas('warning');

        $this->assertSame(1, (int) $shop->fresh()->status);
    }

    public function test_unknown_shop_returns_404(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/admin/shops/99999/approve')
            ->assertNotFound();
    }

    // ── Filtros ──────────────────────────────────────────────────

    public function test_status_filter_narrows_the_list(): void
    {
        $pending = $this->makePendingSeller();
        $approved = $this->makeSeller();

        $this->actingAs($this->makeAdmin())
            ->get('/admin/shops?status=0')
            ->assertOk()
            ->assertSee($pending->shop->name)
            ->assertDontSee($approved->shop->name);
    }

    public function test_search_finds_by_seller_email(): void
    {
        $wanted = $this->makePendingSeller();
        $other = $this->makePendingSeller();

        $this->actingAs($this->makeAdmin())
            ->get('/admin/shops?search='.urlencode($wanted->email))
            ->assertOk()
            ->assertSee($wanted->shop->name)
            ->assertDontSee($other->shop->name);
    }

    public function test_counts_are_shown(): void
    {
        $this->makePendingSeller();
        $this->makeSeller();
        $rejected = $this->makePendingSeller();
        $rejected->shop->update(['status' => 2]);

        $this->actingAs($this->makeAdmin())
            ->get('/admin/shops')
            ->assertOk()
            ->assertViewHas('counts', fn (array $c) => $c['total'] === 3
                && $c['pending'] === 1
                && $c['approved'] === 1
                && $c['rejected'] === 1);
    }

    public function test_screen_flags_sellers_with_unverified_email(): void
    {
        $seller = $this->makePendingSeller();
        $seller->email_verified_at = null;
        $seller->email_verified = 0;
        $seller->save();

        $this->actingAs($this->makeAdmin())
            ->get('/admin/shops')
            ->assertOk()
            ->assertSee('correo sin verificar');
    }

    public function test_shop_relation_is_not_broken_by_missing_user(): void
    {
        $shop = Shop::create([
            'user_id' => $this->makeCustomer()->id,
            'name' => 'Tienda huérfana',
            'email' => 'huerfana@example.com',
            'address' => 'Sin dirección',
            'id_front_image' => 'sellers/id/f.jpg',
            'id_back_image' => 'sellers/id/b.jpg',
            'status' => 0,
        ]);

        $this->actingAs($this->makeAdmin())
            ->get('/admin/shops')
            ->assertOk()
            ->assertSee($shop->name);
    }
}
