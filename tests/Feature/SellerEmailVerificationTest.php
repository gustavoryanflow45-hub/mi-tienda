<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

class SellerEmailVerificationTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    /** Seller con tienda aprobada pero email sin verificar. */
    protected function makeUnverifiedSeller(): User
    {
        $seller = User::factory()->create([
            'user_type' => 'seller',
            'email_verified_at' => null,
        ]);
        $seller->email_verified = 0;
        $seller->save();
        $this->makeShop($seller, 1);

        return $seller;
    }

    public function test_unverified_seller_is_redirected_not_crashed(): void
    {
        $this->actingAs($this->makeUnverifiedSeller())
            ->get('/seller/products')
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('warning');
    }

    public function test_unverified_seller_sees_the_reason(): void
    {
        $seller = $this->makeUnverifiedSeller();

        $this->actingAs($seller)->get('/seller/products');

        $this->actingAs($seller)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('verificar tu correo', false)
            ->assertSee($seller->email);
    }

    public function test_unverified_seller_cannot_publish_a_product(): void
    {
        $this->actingAs($this->makeUnverifiedSeller())
            ->post('/seller/products', ['name' => 'Producto sin verificar'])
            ->assertRedirect(route('verification.notice'));

        $this->assertDatabaseMissing('products', ['name' => 'Producto sin verificar']);
    }

    public function test_verified_seller_still_gets_in(): void
    {
        $this->actingAs($this->makeSeller())
            ->get('/seller/products')
            ->assertOk();
    }
}
