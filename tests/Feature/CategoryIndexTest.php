<?php

namespace Tests\Feature;

use App\Http\Controllers\CategoryController;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /categories (categories.index): menú lateral con las categorías raíz
 * (más la pestaña virtual "Destacados") y la cuadrícula de subcategorías y
 * productos de cada una. Antes la ruta existía sin método en el controlador
 * y cualquier enlace a ella devolvía un 500.
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

    /** El HTML del panel de una pestaña, de su apertura a su cierre. */
    protected function panel(string $content, string $slug): string
    {
        $panel = substr($content, strpos($content, 'data-cat-panel="'.$slug.'"'));

        return substr($panel, 0, strpos($panel, '</section>'));
    }

    public function test_page_loads_for_guests(): void
    {
        $this->makeCategory('Electrónica');

        $this->get('/categories')
            ->assertOk()
            ->assertSee('Destacados')
            ->assertSee('Electrónica');
    }

    public function test_lists_only_active_root_categories_as_tabs(): void
    {
        $visible = $this->makeCategory('Visible');
        $inactive = $this->makeCategory('Inactiva', ['status' => 0]);
        $child = $this->makeCategory('Hija', ['parent_id' => $visible->id]);

        $response = $this->get('/categories');

        $response->assertOk();
        $response->assertSee('data-cat-tab="'.$visible->slug.'"', false);
        $response->assertDontSee($inactive->name);

        // La hija aparece, pero como círculo dentro del panel de su padre.
        $response->assertDontSee('data-cat-tab="'.$child->slug.'"', false);
        $this->assertStringContainsString(
            route('category.show', $child->slug),
            $this->panel($response->getContent(), $visible->slug)
        );

        // Destacados + la única raíz activa.
        $this->assertSame(2, substr_count($response->getContent(), 'data-cat-tab="'));
    }

    public function test_hides_inactive_subcategories(): void
    {
        $root = $this->makeCategory('Ropa');
        $this->makeCategory('Camisas', ['parent_id' => $root->id]);
        $this->makeCategory('Oculta', ['parent_id' => $root->id, 'status' => 0]);

        $this->get('/categories')
            ->assertSee('Camisas')
            ->assertDontSee('Oculta');
    }

    public function test_panel_shows_published_products_of_the_category_and_its_children(): void
    {
        $root = $this->makeCategory('Hogar');
        $child = $this->makeCategory('Cocina', ['parent_id' => $root->id]);

        $this->makeProduct($root, ['name' => 'Sartén de hierro']);
        $this->makeProduct($child, ['name' => 'Juego de cuchillos']);
        $this->makeProduct($child, ['name' => 'Borrador oculto', 'published' => 0]);

        $panel = $this->panel($this->get('/categories')->getContent(), $root->slug);

        $this->assertStringContainsString('Sartén de hierro', $panel);
        $this->assertStringContainsString('Juego de cuchillos', $panel);
        $this->assertStringNotContainsString('Borrador oculto', $panel);
        $this->assertStringContainsString('2 productos', $panel);
    }

    public function test_panel_caps_products_before_view_all(): void
    {
        $root = $this->makeCategory('Juguetes');

        foreach (range(1, CategoryController::PANEL_PRODUCTS + 2) as $i) {
            $this->makeProduct($root);
        }

        $panel = $this->panel($this->get('/categories')->getContent(), $root->slug);

        $this->assertSame(CategoryController::PANEL_PRODUCTS, substr_count($panel, url('/product/')));
        $this->assertStringContainsString(route('category.show', $root->slug), $panel);
    }

    public function test_empty_category_says_so(): void
    {
        $root = $this->makeCategory('Vacía');

        $this->assertStringContainsString(
            'Aún no hay productos en esta categoría.',
            $this->panel($this->get('/categories')->getContent(), $root->slug)
        );
    }

    public function test_query_string_selects_the_open_tab(): void
    {
        $this->makeCategory('Primera', ['order' => 1]);
        $second = $this->makeCategory('Segunda', ['order' => 2]);

        $content = $this->get('/categories?c='.$second->slug)->getContent();

        $this->assertMatchesRegularExpression('/is-active"[^>]*data-cat-tab="'.$second->slug.'"/', $content);
        $this->assertSame(1, substr_count($content, 'is-active"'));
        $this->assertStringNotContainsString(' hidden', substr($this->panel($content, $second->slug), 0, 200));
        $this->assertStringContainsString(' hidden', substr($this->panel($content, 'destacados'), 0, 200));

        // Un slug desconocido abre Destacados.
        $this->assertMatchesRegularExpression(
            '/is-active"[^>]*data-cat-tab="destacados"/',
            $this->get('/categories?c=no-existe')->getContent()
        );
    }

    public function test_featured_tab_shows_featured_products_or_falls_back_to_best_sellers(): void
    {
        $category = $this->makeCategory('Tecnología');
        $this->makeProduct($category, ['name' => 'Más vendido', 'num_of_sale' => 50]);

        // Sin destacados, la pestaña no abre vacía.
        $this->assertStringContainsString(
            'Más vendido',
            $this->panel($this->get('/categories')->getContent(), 'destacados')
        );

        $this->makeProduct($category, ['name' => 'Elegido a mano', 'featured' => 1]);

        $featured = $this->panel($this->get('/categories')->getContent(), 'destacados');

        $this->assertStringContainsString('Elegido a mano', $featured);
        $this->assertStringNotContainsString('Más vendido', $featured);
    }

    public function test_says_so_when_there_are_no_categories(): void
    {
        $this->get('/categories')
            ->assertOk()
            ->assertSee('Todavía no hay categorías publicadas.', false);
    }

    // ── Category::syncCatalog() ──────────────────────────────────────────

    public function test_sync_catalog_renames_legacy_categories_in_place_and_creates_the_rest(): void
    {
        $legacy = Category::create(['name' => "Women's Fashion", 'slug' => 'womens-fashion', 'icon' => 'cat-womens.webp']);
        $product = $this->makeProduct($legacy);

        $result = Category::syncCatalog();

        $legacy->refresh();
        $this->assertSame('ropa-de-mujer', $legacy->slug);
        $this->assertSame('Ropa de mujer', $legacy->name);
        $this->assertSame('cat-womens.webp', $legacy->icon);
        $this->assertSame('apparel', $legacy->variant_type);
        $this->assertSame($legacy->id, $product->fresh()->category_id);

        $catalog = config('categories.catalog');
        $this->assertSame(count($catalog) - 1, $result['created']);
        $this->assertSame(count($catalog), Category::count());
        $this->assertSame(array_keys($catalog), Category::orderBy('order')->pluck('slug')->all());
        $this->assertSame('footwear', Category::where('slug', 'calzado-de-hombre')->value('variant_type'));
    }

    public function test_sync_catalog_is_idempotent_and_keeps_unknown_roots_at_the_end(): void
    {
        $custom = Category::create(['name' => 'Propia', 'slug' => 'propia', 'order' => 0]);

        Category::syncCatalog();
        $this->assertSame(['created' => 0, 'updated' => 0], Category::syncCatalog());

        $this->assertSame(count(config('categories.catalog')) + 1, $custom->fresh()->order);
    }
}
