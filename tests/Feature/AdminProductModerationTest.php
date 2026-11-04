<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCheckoutData;
use Tests\Concerns\PassesAdminTwoFactor;
use Tests\TestCase;

/**
 * Moderación de productos desde /admin/products: aprobar/desactivar
 * (products.approved), editar con el formulario y el servicio del vendedor,
 * y que un producto desactivado desaparezca de toda la tienda.
 */
class AdminProductModerationTest extends TestCase
{
    use BuildsCheckoutData;
    use PassesAdminTwoFactor;
    use RefreshDatabase;

    protected function product(?User $seller = null): Product
    {
        $product = $this->makeProduct($seller ?? $this->makeSeller());
        ProductStock::create(['product_id' => $product->id, 'price' => $product->unit_price, 'qty' => 5]);

        return $product->fresh();
    }

    public function test_the_list_filters_by_status_and_shows_the_shop(): void
    {
        $seller = $this->makeSeller();
        $onSale = $this->product($seller);
        $disabled = $this->product($seller);
        $disabled->forceFill(['name' => 'Producto vetado', 'approved' => 0])->save();

        $this->actingAsAdmin(Admin::factory()->create())
            ->get('/admin/products?status=disabled')
            ->assertOk()
            ->assertSee('Producto vetado')
            ->assertSee($seller->shop->name)
            ->assertDontSee(route('admin.products.show', $onSale->id), false);
    }

    public function test_disabling_hides_the_product_from_the_whole_storefront(): void
    {
        $product = $this->product();
        $customer = $this->makeCustomer();
        $admin = Admin::factory()->create();

        $this->get("/product/{$product->slug}")->assertOk();

        $this->actingAsAdmin($admin)
            ->post("/admin/products/{$product->id}/disable")
            ->assertSessionHas('success');

        $this->assertSame(0, (int) $product->fresh()->approved);
        // Sigue publicado: published es del vendedor, approved del panel.
        $this->assertSame(1, (int) $product->fresh()->published);

        $this->get("/product/{$product->slug}")->assertNotFound();
        $this->get('/products')->assertOk()->assertDontSee($product->slug, false);
        $this->get('/category/'.$product->category->slug)->assertOk()->assertDontSee($product->slug, false);

        $this->actingAs($customer)
            ->postJson('/cart/addtocart', ['product_id' => $product->id, 'quantity' => 1])
            ->assertNotFound();
        $this->assertDatabaseMissing('carts', ['user_id' => $customer->id, 'product_id' => $product->id]);

        $log = AdminAuditLog::where('action', 'product.disabled')->first();
        $this->assertSame($admin->id, $log->admin_id);
        $this->assertSame($product->added_by, $log->meta['seller_id']);
    }

    public function test_approving_brings_it_back_and_a_repeat_is_a_no_op(): void
    {
        $product = $this->product();
        $product->forceFill(['approved' => 0])->save();
        $admin = Admin::factory()->create();

        $this->actingAsAdmin($admin)->post("/admin/products/{$product->id}/approve")->assertSessionHas('success');
        $this->actingAsAdmin($admin)->post("/admin/products/{$product->id}/approve")->assertSessionHas('warning');

        $this->assertSame(1, AdminAuditLog::where('action', 'product.approved')->count());
        $this->get("/product/{$product->slug}")->assertOk();
    }

    public function test_the_seller_sees_the_moderation_badge_in_their_list(): void
    {
        $seller = $this->makeSeller();
        $seller->forceFill(['email_verified_at' => now()])->save();
        $product = $this->product($seller);
        $product->forceFill(['approved' => 0])->save();

        $this->actingAs($seller)->get('/seller/products')->assertOk()
            ->assertSee(__('Desactivado por moderación'));
    }

    public function test_the_seller_cannot_moderate_through_the_storefront_toggle(): void
    {
        $seller = $this->makeSeller();
        $product = $this->product($seller);
        $product->forceFill(['approved' => 0])->save();

        // Publicar es suyo; aprobar no: su producto sigue fuera de la tienda.
        $this->actingAs($seller)->postJson("/seller/products/{$product->id}/toggle-published")->assertOk();
        $this->actingAs($seller)->postJson("/seller/products/{$product->id}/toggle-published")->assertOk();

        $this->assertSame(0, (int) $product->fresh()->approved);
    }

    public function test_the_panel_edits_with_the_seller_form_rules_and_keeps_the_owner(): void
    {
        $seller = $this->makeSeller();
        $product = $this->product($seller);
        $admin = Admin::factory()->create();

        $this->actingAsAdmin($admin)->get("/admin/products/{$product->id}/edit")
            ->assertOk()
            ->assertSee(route('admin.products.update', $product->id), false);

        $payload = [
            'name' => 'Nombre corregido',
            'category_id' => $product->category_id,
            'unit_price' => 80,
            'unit' => 'pieza',
            'stocks' => [['price' => 80, 'qty' => 3]],
        ];

        $this->actingAsAdmin($admin)
            ->put("/admin/products/{$product->id}", $payload)
            ->assertRedirect(route('admin.products.show', $product->id));

        $fresh = $product->fresh();
        $this->assertSame('Nombre corregido', $fresh->name);
        $this->assertSame($seller->id, (int) $fresh->added_by);
        $this->assertSame(3, (int) $fresh->stocks()->sum('qty'));
        $this->assertSame($admin->id, AdminAuditLog::where('action', 'product.edited')->value('admin_id'));

        // Mismas reglas de variantes que el vendedor: la categoría no usa tallas.
        $this->actingAsAdmin($admin)
            ->from("/admin/products/{$product->id}/edit")
            ->put("/admin/products/{$product->id}", array_merge($payload, ['stocks' => [['size' => 'M', 'price' => 80, 'qty' => 3]]]))
            ->assertSessionHasErrors('stocks.0.size');
    }

    public function test_the_edit_form_renders_again_after_a_validation_error(): void
    {
        $product = $this->product();

        $this->actingAsAdmin(Admin::factory()->create())
            ->from("/admin/products/{$product->id}/edit")
            ->followingRedirects()
            ->put("/admin/products/{$product->id}", [
                'name' => '',
                'category_id' => $product->category_id,
                'unit_price' => 80,
                'unit' => 'pieza',
                'stocks' => [['price' => 80, 'qty' => 3]],
            ])
            ->assertOk()
            ->assertSee(route('admin.products.update', $product->id), false);
    }

    public function test_the_product_page_shows_stock_and_seller(): void
    {
        $seller = $this->makeSeller();
        $product = $this->product($seller);

        $this->actingAsAdmin(Admin::factory()->create())
            ->get("/admin/products/{$product->id}")
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee($seller->email)
            ->assertSee(route('admin.products.disable', $product->id), false);
    }
}
