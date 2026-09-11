<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

class SellerShopApprovalTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    public function test_pending_shop_cannot_reach_seller_panel(): void
    {
        $seller = $this->makePendingSeller();

        $this->actingAs($seller)
            ->get('/seller/products')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('warning');
    }

    /** El rebote debe explicarse en pantalla, no dejar al vendedor a ciegas. */
    public function test_pending_seller_sees_the_reason_on_the_dashboard(): void
    {
        $seller = $this->makePendingSeller();

        $this->actingAs($seller)
            ->get('/seller/products')
            ->assertRedirect(route('dashboard'));

        $this->actingAs($seller)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('pendiente de aprobación', false);
    }

    public function test_pending_shop_cannot_publish_a_product(): void
    {
        $seller = $this->makePendingSeller();

        $this->actingAs($seller)
            ->post('/seller/products', ['name' => 'Producto pirata'])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('products', ['name' => 'Producto pirata']);
    }

    public function test_rejected_shop_is_told_so(): void
    {
        $seller = User::factory()->create(['user_type' => 'seller']);
        $this->makeShop($seller, 2);

        $this->actingAs($seller)
            ->get('/seller/orders')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('warning', 'Tu tienda fue rechazada. Contacta con soporte para más información.');
    }

    public function test_seller_without_shop_is_sent_to_register_one(): void
    {
        $seller = User::factory()->create(['user_type' => 'seller']);

        $this->actingAs($seller)
            ->get('/seller/products')
            ->assertRedirect(route('dashboard'));

        $this->assertSame(0, Shop::where('user_id', $seller->id)->count());
    }

    public function test_approved_shop_reaches_seller_panel(): void
    {
        $this->actingAs($this->makeSeller())
            ->get('/seller/products')
            ->assertOk();
    }

    public function test_admin_reaches_seller_panel_without_a_shop(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin']);

        $this->actingAs($admin)
            ->get('/seller/products')
            ->assertOk();
    }

    /** Registrarse como tienda no debe conceder acceso inmediato. */
    public function test_fresh_registration_starts_pending(): void
    {
        $seller = $this->makePendingSeller();

        $this->assertSame(0, (int) $seller->shop->status);

        $this->actingAs($seller)
            ->get('/seller/products/create')
            ->assertRedirect(route('dashboard'));
    }

    /** user_type no debe poder llegar desde el request. */
    public function test_registration_cannot_self_assign_admin(): void
    {
        $this->post('/register', [
            'first_name' => 'Mala',
            'last_name' => 'Fe',
            'email' => 'malafe@example.com',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'user_type' => 'admin',
            'balance' => 999999,
        ]);

        $user = User::where('email', 'malafe@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('customer', $user->user_type);
        $this->assertEquals(0.0, (float) $user->balance);
    }
}
