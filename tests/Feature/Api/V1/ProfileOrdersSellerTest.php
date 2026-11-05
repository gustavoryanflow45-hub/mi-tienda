<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Shop;
use App\Models\User;
use App\Support\ApiAbilities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

/**
 * /api/v1/profile, /api/v1/orders y /api/v1/seller: perfil propio, pedidos
 * del comprador y el panel del vendedor con las mismas reglas que la web
 * (ProductService, OrderFulfillmentService, OrderPolicy, SettlementService).
 */
class ProfileOrdersSellerTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    protected function api(User $user, ?array $abilities = null): static
    {
        $token = $user->createToken('test', $abilities ?? ApiAbilities::for($user), now()->addDay())->plainTextToken;

        // El guard de Sanctum recuerda al usuario entre peticiones del mismo test.
        $this->app['auth']->forgetGuards();

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    protected function paidOrder(User $customer, User $seller, float $total = 100): Order
    {
        $order = $this->makePendingOrder($customer, $seller, $total);
        $order->forceFill(['payment_status' => 'paid', 'status' => 'pagado', 'delivery_status' => 'pending'])->save();
        $order->orderDetails()->update(['payment_status' => 'paid', 'delivery_status' => 'pending']);

        return $order;
    }

    // ── Perfil ───────────────────────────────────────────────────

    public function test_the_profile_can_be_read_and_edited_but_not_the_email(): void
    {
        $user = $this->makeCustomer();

        $this->api($user)->patchJson('/api/v1/profile', ['name' => 'Ana Nueva', 'phone' => '0991112222', 'email' => 'otro@correo.com', 'user_type' => 'seller'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Ana Nueva')
            ->assertJsonPath('data.phone', '0991112222')
            ->assertJsonPath('data.type', 'customer');

        $user->refresh();
        $this->assertNotSame('otro@correo.com', $user->email);
        $this->assertSame('customer', $user->user_type);

        $this->api($user, ['profile:read'])->patchJson('/api/v1/profile', ['name' => 'X'])->assertForbidden();
    }

    public function test_the_avatar_is_uploaded(): void
    {
        Storage::fake('public');
        $user = $this->makeCustomer();

        $url = $this->api($user)->post('/api/v1/profile/avatar', ['avatar' => UploadedFile::fake()->image('yo.jpg', 400, 400)])
            ->assertOk()
            ->json('data.avatar_url');

        $this->assertNotNull($url);
        Storage::disk('public')->assertExists($user->refresh()->avatar);
    }

    public function test_changing_the_password_closes_the_other_devices(): void
    {
        $user = $this->makeCustomer();
        $user->forceFill(['password' => Hash::make('Clave1234')])->save();
        $user->createToken('otro-movil', ApiAbilities::for($user));

        $this->api($user)->putJson('/api/v1/profile/password', [
            'current_password' => 'incorrecta', 'password' => 'Nueva12345', 'password_confirmation' => 'Nueva12345',
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');

        $this->api($user)->putJson('/api/v1/profile/password', [
            'current_password' => 'Clave1234', 'password' => 'Nueva12345', 'password_confirmation' => 'Nueva12345',
        ])->assertOk();

        $this->assertTrue(Hash::check('Nueva12345', $user->refresh()->password));
        // Queda solo el token con el que se cambió.
        $this->assertSame(1, $user->tokens()->count());
    }

    // ── Pedidos del comprador ────────────────────────────────────

    public function test_a_buyer_lists_and_reads_only_their_own_orders(): void
    {
        $seller = $this->makeSeller();
        $buyer = $this->makeCustomer();
        $mine = $this->paidOrder($buyer, $seller, 80);
        $this->makePendingOrder($buyer, $seller, 20);
        $foreign = $this->paidOrder($this->makeCustomer(), $seller);

        $this->api($buyer)->getJson('/api/v1/orders')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        $this->api($buyer)->getJson('/api/v1/orders?payment_status=paid')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id);

        $this->api($buyer)->getJson("/api/v1/orders/{$mine->id}")
            ->assertOk()
            ->assertJsonPath('data.code', $mine->code)
            ->assertJsonPath('data.tracking.confirmed_at', null);

        $this->api($buyer)->getJson("/api/v1/orders/{$foreign->id}")->assertNotFound();
    }

    // ── Vendedor: acceso ─────────────────────────────────────────

    public function test_seller_endpoints_need_the_ability_and_an_approved_shop_on_every_request(): void
    {
        $customer = $this->makeCustomer();
        $this->api($customer)->getJson('/api/v1/seller/dashboard')->assertForbidden()->assertJsonPath('code', 'forbidden');

        // Token emitido con la tienda aprobada; luego el panel la rechaza.
        $seller = $this->makeSeller();
        $client = $this->api($seller);
        $client->getJson('/api/v1/seller/dashboard')->assertOk();

        $seller->shop->forceFill(['status' => Shop::REJECTED])->save();
        $this->app['auth']->forgetGuards();

        $client->getJson('/api/v1/seller/dashboard')->assertForbidden()->assertJsonPath('code', 'shop_not_approved');
        $client->getJson('/api/v1/seller/products')->assertForbidden()->assertJsonPath('code', 'shop_not_approved');
    }

    public function test_the_dashboard_shows_the_settlement_cycle(): void
    {
        $seller = $this->makeSeller();
        $order = $this->paidOrder($this->makeCustomer(), $seller, 100);
        $order->orderDetails()->update(['delivery_status' => 'delivered']);
        $this->paidOrder($this->makeCustomer(), $seller, 40);

        $this->api($seller)->getJson('/api/v1/seller/dashboard')
            ->assertOk()
            ->assertJsonPath('data.pending_orders', 1)
            ->assertJsonPath('data.current_cycle.total_sales', 100)
            ->assertJsonPath('data.current_cycle.net_amount', 75)
            ->assertJsonPath('data.last_settlement', null);
    }

    // ── Vendedor: productos ──────────────────────────────────────

    protected function shoeCategory(): Category
    {
        return Category::create(['name' => 'Calzado', 'slug' => 'calzado', 'status' => 1, 'variant_type' => 'footwear']);
    }

    public function test_a_seller_creates_a_product_with_combinations_and_images(): void
    {
        Storage::fake('public');
        $seller = $this->makeSeller();
        $category = $this->shoeCategory();
        $size = config('variants.types.footwear.sizes')[0];

        $options = $this->api($seller)->getJson('/api/v1/seller/products/options')->assertOk();
        $this->assertContains('negro', collect($options->json('data.colors'))->pluck('key'));
        $this->assertContains($category->id, collect($options->json('data.categories'))->pluck('id'));

        $id = $this->api($seller)->post('/api/v1/seller/products', [
            'name' => 'Zapatilla Trail',
            'category_id' => $category->id,
            'unit_price' => 60,
            'unit' => 'par',
            'published' => '1',
            'thumbnail' => UploadedFile::fake()->image('thumb.jpg', 600, 600),
            'photos' => [UploadedFile::fake()->image('a.jpg', 300, 300)],
            'stocks' => [
                ['size' => $size, 'color' => 'negro', 'price' => 60, 'qty' => 4],
                ['size' => $size, 'color' => 'blanco', 'price' => 62, 'qty' => 2],
            ],
            // Fuera del formulario: se ignora.
            'added_by' => $this->makeSeller()->id,
            'approved' => 0,
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.on_sale', true)
            ->assertJsonCount(2, 'data.stocks')
            ->assertJsonCount(1, 'data.photos')
            ->json('data.id');

        $product = Product::findOrFail($id);
        $this->assertSame($seller->id, (int) $product->added_by);
        $this->assertTrue((bool) $product->approved);
        $this->assertSame(1, (int) $product->variant_product);
    }

    public function test_product_rules_are_the_same_as_the_web_form(): void
    {
        $seller = $this->makeSeller();
        $category = Category::create(['name' => 'Hogar', 'slug' => 'hogar', 'status' => 1, 'variant_type' => 'none']);

        // Talla en una categoría sin tallas, color fuera de la paleta y combinación repetida.
        foreach ([
            [['size' => 'M', 'price' => 10, 'qty' => 1]],
            [['color' => 'fucsia-neon', 'price' => 10, 'qty' => 1]],
            [['color' => 'negro', 'price' => 10, 'qty' => 1], ['color' => 'negro', 'price' => 10, 'qty' => 1]],
        ] as $stocks) {
            Storage::fake('public');
            $this->api($seller)->post('/api/v1/seller/products', [
                'name' => 'Lámpara', 'category_id' => $category->id, 'unit_price' => 10, 'unit' => 'u',
                'thumbnail' => UploadedFile::fake()->image('t.jpg'), 'stocks' => $stocks,
            ], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonPath('code', 'validation_failed');
        }

        $this->assertSame(0, Product::count());
    }

    public function test_a_seller_edits_and_publishes_only_their_own_products(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, 30);
        ProductStock::create(['product_id' => $product->id, 'color' => 'negro', 'price' => 30, 'qty' => 5]);
        $product->forceFill(['featured' => 1, 'unit' => 'pieza'])->save();

        // Edición parcial en JSON: lo que no llega (stocks, featured) se conserva.
        $this->api($seller)->putJson("/api/v1/seller/products/{$product->id}", ['name' => 'Producto renombrado', 'unit_price' => 35])
            ->assertOk()
            ->assertJsonPath('data.name', 'Producto renombrado')
            ->assertJsonPath('data.unit_price', 35)
            ->assertJsonCount(1, 'data.stocks')
            ->assertJsonPath('data.stocks.0.color', 'negro');
        $this->assertTrue((bool) $product->refresh()->featured);

        // Con imágenes: multipart como POST con _method=PUT.
        Storage::fake('public');
        $this->api($seller)->post("/api/v1/seller/products/{$product->id}", [
            '_method' => 'PUT', 'thumbnail' => UploadedFile::fake()->image('nueva.jpg'),
        ], ['Accept' => 'application/json'])->assertOk();
        Storage::disk('public')->assertExists($product->refresh()->thumbnail);

        $this->api($seller)->postJson("/api/v1/seller/products/{$product->id}/toggle-published")
            ->assertOk()
            ->assertJsonPath('data.published', false)
            ->assertJsonPath('data.on_sale', false);

        $intruder = $this->makeSeller();
        $this->api($intruder)->getJson("/api/v1/seller/products/{$product->id}")->assertNotFound();
        $this->api($intruder)->putJson("/api/v1/seller/products/{$product->id}", ['name' => 'Robado'])->assertNotFound();
        $this->api($intruder)->postJson("/api/v1/seller/products/{$product->id}/toggle-published")->assertNotFound();
        $this->api($intruder)->getJson('/api/v1/seller/products')->assertOk()->assertJsonCount(0, 'data');

        $this->assertSame('Producto renombrado', $product->refresh()->name);
    }

    // ── Vendedor: ventas ─────────────────────────────────────────

    public function test_a_seller_sees_only_paid_orders_with_their_lines_and_moves_them_forward(): void
    {
        Notification::fake();
        $seller = $this->makeSeller();
        $other = $this->makeSeller();
        $buyer = $this->makeCustomer();

        $order = $this->paidOrder($buyer, $seller, 50);
        // Línea de otra tienda en el mismo pedido: no la ve.
        $order->orderDetails()->create(['seller_id' => $other->id, 'product_id' => $this->makeProduct($other)->id, 'product_name' => 'Ajeno', 'price' => 20, 'quantity' => 1]);
        $unpaid = $this->makePendingOrder($buyer, $seller, 10);

        $this->api($seller)->getJson('/api/v1/seller/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $order->id)
            ->assertJsonCount(1, 'data.0.items')
            ->assertJsonPath('data.0.seller_total', 50)
            ->assertJsonPath('data.0.buyer.email', $buyer->email)
            ->assertJsonPath('data.0.actions.confirm', true);

        $this->api($seller)->getJson("/api/v1/seller/orders/{$unpaid->id}")->assertNotFound();
        $this->api($this->makeSeller())->postJson("/api/v1/seller/orders/{$order->id}/confirm")->assertNotFound();

        // El orden de los pasos lo impone OrderFulfillmentService.
        $this->api($seller)->postJson("/api/v1/seller/orders/{$order->id}/send-to-warehouse")
            ->assertStatus(422)->assertJsonPath('code', 'business_rule');

        $this->api($seller)->postJson("/api/v1/seller/orders/{$order->id}/confirm")
            ->assertOk()
            ->assertJsonPath('data.delivery_status', 'confirmed')
            ->assertJsonPath('data.actions.send_to_warehouse', true);

        $this->api($seller)->postJson("/api/v1/seller/orders/{$order->id}/send-to-warehouse")
            ->assertOk()
            ->assertJsonPath('data.delivery_status', 'warehouse');

        $this->assertNotNull($order->refresh()->warehouse_at);
    }
}
