<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

class ProductVariantTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    /** El middleware del panel de vendedor exige email verificado. */
    protected function makeVerifiedSeller(): User
    {
        return User::factory()->create([
            'user_type' => 'seller',
            'email_verified_at' => now(),
        ]);
    }

    protected function makeCategory(string $slug, string $variantType): Category
    {
        return Category::firstOrCreate(
            ['slug' => $slug],
            ['name' => ucfirst($slug), 'status' => 1, 'variant_type' => $variantType],
        );
    }

    /** Zapato con 2 tallas x 2 colores; "9-blanco" nace agotado. */
    protected function makeShoe(User $seller): Product
    {
        $product = Product::create([
            'name' => 'Zapato',
            'slug' => 'zapato-'.uniqid(),
            'category_id' => $this->makeCategory('zapatos', 'footwear')->id,
            'added_by' => $seller->id,
            'unit_price' => 59.90,
            'variant_product' => 1,
        ]);

        foreach ([['9', 'negro', 3], ['9', 'blanco', 0], ['10', 'negro', 7]] as [$size, $color, $qty]) {
            ProductStock::create([
                'product_id' => $product->id,
                'size' => $size,
                'color' => $color,
                'price' => 59.90,
                'qty' => $qty,
            ]);
        }

        return $product->load('stocks', 'category');
    }

    public function test_variant_key_combines_size_and_color(): void
    {
        $this->assertSame('9-negro', ProductStock::buildVariant('9', 'negro'));
        $this->assertSame('negro', ProductStock::buildVariant(null, 'negro'));
        $this->assertSame('9', ProductStock::buildVariant('9', null));
        $this->assertSame('', ProductStock::buildVariant(null, null));
    }

    public function test_stock_row_keeps_its_variant_key_in_sync(): void
    {
        $product = $this->makeShoe($this->makeSeller());
        $stock = $product->stockFor('9', 'negro');

        $this->assertNotNull($stock);
        $this->assertSame('9-negro', $stock->variant);

        $stock->update(['color' => 'blanco']);

        $this->assertSame('9-blanco', $stock->fresh()->variant);
    }

    public function test_sizes_and_colors_come_from_columns_not_from_the_name(): void
    {
        $product = $this->makeShoe($this->makeSeller());

        // "coral" no estaba en la antigua lista de nombres de color y por eso
        // se pintaba como talla; ahora la columna manda.
        ProductStock::create([
            'product_id' => $product->id, 'size' => '10', 'color' => 'coral',
            'price' => 59.90, 'qty' => 2,
        ]);

        $product->load('stocks');

        $this->assertSame(['9', '10'], $product->availableSizes());
        $this->assertContains('coral', $product->availableColors());
        $this->assertNotContains('coral', $product->availableSizes());
    }

    public function test_categories_without_sizes_only_offer_color(): void
    {
        $category = $this->makeCategory('electronics', 'none');

        $this->assertFalse($category->usesSizes());
        $this->assertSame([], $category->sizeOptions());
    }

    public function test_unknown_variant_type_degrades_to_color_only(): void
    {
        $category = $this->makeCategory('rarezas', 'tipo-que-no-existe');

        $this->assertFalse($category->usesSizes());
    }

    public function test_adding_a_valid_combination_to_the_cart(): void
    {
        $product = $this->makeShoe($this->makeSeller());

        $this->actingAs($this->makeCustomer())
            ->postJson(route('cart.add'), [
                'product_id' => $product->id,
                'variation' => '10-negro',
                'quantity' => 2,
            ])
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('carts', [
            'product_id' => $product->id,
            'variation' => '10-negro',
            'quantity' => 2,
        ]);
    }

    public function test_a_sold_out_combination_is_rejected(): void
    {
        $product = $this->makeShoe($this->makeSeller());

        $this->actingAs($this->makeCustomer())
            ->postJson(route('cart.add'), [
                'product_id' => $product->id,
                'variation' => '9-blanco',
                'quantity' => 1,
            ])
            ->assertJson(['status' => 'error']);

        $this->assertDatabaseCount('carts', 0);
    }

    /**
     * Antes, una variación inexistente dejaba $stock en null y las
     * validaciones se saltaban: el producto entraba al carrito a precio base
     * y sin tope de cantidad.
     */
    public function test_an_unknown_combination_does_not_slip_through_at_base_price(): void
    {
        $product = $this->makeShoe($this->makeSeller());

        $this->actingAs($this->makeCustomer())
            ->postJson(route('cart.add'), [
                'product_id' => $product->id,
                'variation' => '11-rojo',
                'quantity' => 500,
            ])
            ->assertJson(['status' => 'error']);

        $this->assertDatabaseCount('carts', 0);
    }

    public function test_a_variant_product_requires_choosing_a_combination(): void
    {
        $product = $this->makeShoe($this->makeSeller());

        $this->actingAs($this->makeCustomer())
            ->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1])
            ->assertJson(['status' => 'error']);

        $this->assertDatabaseCount('carts', 0);
    }

    public function test_quantity_cannot_exceed_the_stock_of_that_combination(): void
    {
        $product = $this->makeShoe($this->makeSeller());

        $this->actingAs($this->makeCustomer())
            ->postJson(route('cart.add'), [
                'product_id' => $product->id,
                'variation' => '10-negro',
                'quantity' => 99,
            ])
            ->assertJson(['status' => 'error']);

        $this->assertDatabaseCount('carts', 0);
    }

    public function test_a_product_without_variants_still_works(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller);
        ProductStock::create([
            'product_id' => $product->id, 'price' => 100, 'qty' => 5,
        ]);

        $this->actingAs($this->makeCustomer())
            ->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1])
            ->assertJson(['status' => 'success']);
    }

    public function test_seller_cannot_save_a_size_in_a_category_without_sizes(): void
    {
        $seller = $this->makeVerifiedSeller();
        $category = $this->makeCategory('electronics', 'none');

        $this->actingAs($seller)
            ->post(route('seller.products.store'), [
                'name' => 'Televisor',
                'category_id' => $category->id,
                'unit_price' => 300,
                'unit' => 'pieza',
                'thumbnail' => UploadedFile::fake()->create('tv.jpg', 100, 'image/jpeg'),
                'stocks' => [
                    ['size' => 'M', 'color' => 'negro', 'price' => 300, 'qty' => 2],
                ],
            ])
            ->assertSessionHasErrors('stocks.0.size');
    }

    public function test_seller_cannot_save_duplicated_combinations(): void
    {
        $seller = $this->makeVerifiedSeller();
        $category = $this->makeCategory('zapatos', 'footwear');

        $this->actingAs($seller)
            ->post(route('seller.products.store'), [
                'name' => 'Zapato',
                'category_id' => $category->id,
                'unit_price' => 60,
                'unit' => 'par',
                'thumbnail' => UploadedFile::fake()->create('zapato.jpg', 100, 'image/jpeg'),
                'stocks' => [
                    ['size' => '9', 'color' => 'negro', 'price' => 60, 'qty' => 1],
                    ['size' => '9', 'color' => 'negro', 'price' => 60, 'qty' => 4],
                ],
            ])
            ->assertSessionHasErrors('stocks');
    }

    public function test_seller_cannot_save_a_color_outside_the_palette(): void
    {
        $seller = $this->makeVerifiedSeller();
        $category = $this->makeCategory('zapatos', 'footwear');

        $this->actingAs($seller)
            ->post(route('seller.products.store'), [
                'name' => 'Zapato',
                'category_id' => $category->id,
                'unit_price' => 60,
                'unit' => 'par',
                'thumbnail' => UploadedFile::fake()->create('zapato.jpg', 100, 'image/jpeg'),
                'stocks' => [
                    ['size' => '9', 'color' => 'fucsia-inventado', 'price' => 60, 'qty' => 1],
                ],
            ])
            ->assertSessionHasErrors('stocks.0.color');
    }
}
