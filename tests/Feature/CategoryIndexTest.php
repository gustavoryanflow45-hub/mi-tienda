<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /categories (categories.index). La ruta existía y estaba enlazada desde
 * el home y el header, pero el controlador no tenía el método: cualquiera de
 * esos enlaces devolvía un 500.
 */
class CategoryIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCategory(string $name, array $attrs = []): Category
    {
        return Category::create(array_merge([
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name).'-'.uniqid(),
            'status' => 1,
        ], $attrs));
    }

    protected function makeProduct(Category $category, array $attrs = []): Product
    {
        $seller = User::factory()->create(['user_type' => 'seller']);

        return Product::create(array_merge([
            'name' => 'Producto '.uniqid(),
            'slug' => 'producto-'.uniqid(),
            'category_id' => $category->id,
            'added_by' => $seller->id,
            'unit_price' => 10,
            'published' => 1,
        ], $attrs));
    }

    public function test_page_loads_for_guests(): void
    {
        $this->makeCategory('Electrónica');

        $this->get('/categories')
            ->assertOk()
            ->assertSee('Electrónica');
    }

    public function test_lists_only_active_root_categories(): void
    {
        $visible = $this->makeCategory('Visible');
        $inactive = $this->makeCategory('Inactiva', ['status' => 0]);
        $child = $this->makeCategory('Hija', ['parent_id' => $visible->id]);

        $response = $this->get('/categories');

        $response->assertOk();
        $response->assertSee($visible->name);
        $response->assertDontSee($inactive->name);

        // La hija aparece, pero como enlace dentro de su padre, no como tarjeta.
        $response->assertSee($child->name);
        $this->assertSame(1, substr_count($response->getContent(), 'class="cat-card"'));
    }

    public function test_shows_subcategories_of_each_root(): void
    {
        $root = $this->makeCategory('Ropa');
        $this->makeCategory('Camisas', ['parent_id' => $root->id]);
        $this->makeCategory('Oculta', ['parent_id' => $root->id, 'status' => 0]);

        $response = $this->get('/categories');

        $response->assertOk();
        $response->assertSee('Camisas');
        $response->assertDontSee('Oculta');
    }

    public function test_root_without_children_says_so(): void
    {
        $this->makeCategory('Sola');

        $this->get('/categories')->assertSee('Sin subcategorías', false);
    }

    /** El conteo de una raíz incluye los productos de sus subcategorías. */
    public function test_product_count_includes_children(): void
    {
        $root = $this->makeCategory('Hogar');
        $child = $this->makeCategory('Cocina', ['parent_id' => $root->id]);

        $this->makeProduct($root);
        $this->makeProduct($child);
        $this->makeProduct($child, ['published' => 0]);   // sin publicar, no cuenta

        $this->get('/categories')
            ->assertOk()
            ->assertSee('2 productos');
    }

    public function test_empty_category_reads_zero_in_plural(): void
    {
        $this->makeCategory('Vacía');

        $this->get('/categories')->assertSee('0 productos');
    }

    public function test_says_so_when_there_are_no_categories(): void
    {
        $this->get('/categories')
            ->assertOk()
            ->assertSee('Todavía no hay categorías publicadas.', false);
    }
}
