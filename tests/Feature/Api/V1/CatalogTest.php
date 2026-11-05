<?php

namespace Tests\Feature\Api\V1;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

/**
 * Catálogo público de /api/v1: solo productos a la venta, mismos filtros
 * que la web (CatalogService) y las mismas combinaciones y precios que la
 * ficha web y el carrito.
 */
class CatalogTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    protected User $seller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seller = $this->makeSeller();
    }

    protected function category(string $slug, ?Category $parent = null, int $status = 1, string $type = 'none'): Category
    {
        return Category::create([
            'name' => ucfirst($slug), 'slug' => $slug, 'parent_id' => $parent?->id,
            'status' => $status, 'variant_type' => $type,
        ]);
    }

    protected function product(string $name, Category $category, float $price = 50, array $attributes = []): Product
    {
        $product = Product::create($attributes + [
            'name' => $name, 'slug' => \Illuminate\Support\Str::slug($name).'-'.uniqid(),
            'category_id' => $category->id, 'added_by' => $this->seller->id, 'unit_price' => $price,
        ]);
        ProductStock::create(['product_id' => $product->id, 'price' => $price, 'qty' => 3]);

        return $product;
    }

    public function test_the_catalog_is_public_and_only_shows_products_on_sale(): void
    {
        $cat = $this->category('ropa');
        $visible = $this->product('Camisa visible', $cat);
        $disabled = $this->product('Camisa vetada', $cat, 50, ['approved' => 0]);
        $unpublished = $this->product('Camisa oculta', $cat, 50, ['published' => 0]);

        $slugs = collect($this->getJson('/api/v1/products')->assertOk()->json('data'))->pluck('slug');

        $this->assertContains($visible->slug, $slugs);
        $this->assertNotContains($disabled->slug, $slugs);
        $this->assertNotContains($unpublished->slug, $slugs);

        $this->getJson("/api/v1/products/{$visible->slug}")->assertOk();
        $this->getJson("/api/v1/products/{$disabled->slug}")->assertNotFound()->assertJsonPath('code', 'not_found');
        $this->getJson("/api/v1/products/{$unpublished->id}")->assertNotFound();
    }

    public function test_products_are_paginated_and_per_page_is_capped(): void
    {
        $cat = $this->category('ropa');
        foreach (range(1, 5) as $i) {
            $this->product("Prenda {$i}", $cat);
        }

        $this->getJson('/api/v1/products?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonStructure(['links' => ['first', 'last', 'prev', 'next']]);

        $this->getJson('/api/v1/products?per_page=500')->assertStatus(422)->assertJsonValidationErrors('per_page');
    }

    public function test_filters_match_the_web_catalog(): void
    {
        $ropa = $this->category('ropa');
        $camisas = $this->category('camisas', $ropa);
        $hogar = $this->category('hogar');
        $marca = Brand::create(['name' => 'Andes', 'slug' => 'andes', 'status' => 1]);

        $hija = $this->product('Camisa Andes', $camisas, 30, ['brand_id' => $marca->id, 'featured' => 1]);
        $raiz = $this->product('Pantalón', $ropa, 80);
        $otra = $this->product('Lámpara', $hogar, 20);

        $slugs = fn (string $query) => collect($this->getJson('/api/v1/products?'.$query)->assertOk()->json('data'))->pluck('slug')->all();

        // La categoría incluye sus subcategorías.
        $this->assertEqualsCanonicalizing([$hija->slug, $raiz->slug], $slugs('category=ropa'));
        // Búsqueda sin distinguir mayúsculas.
        $this->assertSame([$hija->slug], $slugs('q=CAMISA'));
        $this->assertSame([$hija->slug], $slugs('brand='.$marca->id));
        $this->assertSame([$hija->slug], $slugs('featured=1'));
        $this->assertEqualsCanonicalizing([$hija->slug, $otra->slug], $slugs('max_price=40'));
        $this->assertSame([$otra->slug, $hija->slug, $raiz->slug], $slugs('sort=price_asc'));

        $this->getJson('/api/v1/products?category=no-existe')->assertStatus(422)->assertJsonValidationErrors('category');
        $this->getJson('/api/v1/products?sort=azar')->assertStatus(422)->assertJsonPath('code', 'validation_failed');
        $this->getJson('/api/v1/products?min_price=50&max_price=10')->assertStatus(422)->assertJsonValidationErrors('max_price');
    }

    public function test_the_detail_exposes_combinations_with_the_price_the_cart_charges(): void
    {
        $zapatos = $this->category('zapatos', null, 1, 'footwear');
        $shoe = Product::create([
            'name' => 'Zapatilla Runner', 'slug' => 'zapatilla-runner', 'category_id' => $zapatos->id,
            'added_by' => $this->seller->id, 'unit_price' => 100, 'discount' => 10, 'discount_type' => 'percent',
            'photos' => ['products/photos/a.webp'],
            'description' => '<p>Ligera <strong>y</strong> cómoda</p><script>alert(1)</script><p>Para correr</p>',
        ]);
        ProductStock::create(['product_id' => $shoe->id, 'size' => '9', 'color' => 'negro', 'price' => 100, 'qty' => 4]);
        ProductStock::create(['product_id' => $shoe->id, 'size' => '10', 'color' => 'negro', 'price' => 110, 'qty' => 0]);

        $data = $this->getJson('/api/v1/products/zapatilla-runner')->assertOk()->json('data');

        $this->assertSame(90, (int) round($data['price']));
        $this->assertEquals(100, $data['original_price']);
        $this->assertSame(['type' => 'percent', 'value' => 10], $data['discount']);
        $this->assertTrue($data['has_variants']);
        $this->assertSame(['9', '10'], $data['variants']['sizes']);
        $this->assertSame('negro', $data['variants']['colors'][0]['key']);

        $combo = collect($data['variants']['combinations'])->keyBy('variation');
        $this->assertEquals(90, $combo['9-negro']['price']);
        $this->assertEquals(100, $combo['9-negro']['original_price']);
        $this->assertTrue($combo['9-negro']['available']);
        $this->assertEquals(99, $combo['10-negro']['price']);
        $this->assertFalse($combo['10-negro']['available']);

        // Mismo precio que cobra el carrito web.
        $this->actingAs($this->makeCustomer())
            ->postJson('/cart/addtocart', ['product_id' => $shoe->id, 'variation' => '9-negro', 'quantity' => 1]);
        $this->assertDatabaseHas('carts', ['product_id' => $shoe->id, 'variation' => '9-negro', 'price' => 90]);

        // La descripción va como texto: sin etiquetas ni scripts.
        $this->assertStringNotContainsString('<', $data['description']);
        $this->assertSame("Ligera y cómoda\nPara correr", $data['description']);
        $this->assertStringEndsWith('storage/products/photos/a.webp', $data['photos'][0]);
        $this->assertSame($this->seller->shop->name, $data['shop']['name']);

        // También por id.
        $this->getJson("/api/v1/products/{$shoe->id}")->assertOk()->assertJsonPath('data.slug', 'zapatilla-runner');
    }

    public function test_related_products_exclude_itself_and_products_not_on_sale(): void
    {
        $cat = $this->category('ropa');
        $main = $this->product('Principal', $cat);
        $other = $this->product('Hermano', $cat);
        $this->product('Vetado', $cat, 50, ['approved' => 0]);

        $related = collect($this->getJson("/api/v1/products/{$main->slug}")->json('data.related'))->pluck('slug')->all();

        $this->assertSame([$other->slug], $related);
    }

    public function test_categories_tree_only_has_active_categories(): void
    {
        $ropa = $this->category('ropa');
        $this->category('camisas', $ropa);
        $this->category('ocultas', $ropa, 0);
        $this->category('archivada', null, 0);

        $tree = collect($this->getJson('/api/v1/categories')->assertOk()->json('data'));

        $this->assertNotContains('archivada', $tree->pluck('slug'));
        $this->assertSame(['camisas'], collect($tree->firstWhere('slug', 'ropa')['children'])->pluck('slug')->all());

        $this->getJson('/api/v1/categories/camisas')->assertOk()->assertJsonPath('data.parent.slug', 'ropa');
        $this->getJson('/api/v1/categories/archivada')->assertNotFound();
    }

    public function test_home_brings_the_sections_in_one_request(): void
    {
        $cat = $this->category('ropa');
        $featured = $this->product('Destacada', $cat, 40, ['featured' => 1]);
        $this->product('Vetada', $cat, 40, ['featured' => 1, 'approved' => 0]);
        Banner::create(['title' => 'Promo', 'image' => 'banners/promo.webp', 'type' => 'slider', 'status' => 1, 'order' => 1]);
        Brand::create(['name' => 'Andes', 'slug' => 'andes', 'status' => 1]);
        Brand::create(['name' => 'Oculta', 'slug' => 'oculta', 'status' => 0]);

        $home = $this->getJson('/api/v1/home')->assertOk()->json('data');

        $this->assertSame([$featured->slug], collect($home['featured'])->pluck('slug')->all());
        $this->assertSame('Promo', $home['banners'][0]['title']);
        $this->assertNotEmpty($home['new']);
        $this->assertNotEmpty($home['best_selling']);

        $this->assertSame(['andes'], collect($this->getJson('/api/v1/brands')->json('data'))->pluck('slug')->all());
    }
}
