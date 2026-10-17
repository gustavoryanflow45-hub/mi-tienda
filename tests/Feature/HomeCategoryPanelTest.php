<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Panel lateral de categorías del home: el desplegable de subcategorías
 * tapaba el slider incluso en categorías sin subcategorías.
 */
class HomeCategoryPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCategory(string $name, array $attrs = []): Category
    {
        return Category::create(array_merge([
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'status' => 1,
        ], $attrs));
    }

    /** El <li> del panel lateral para una categoría. */
    protected function navItem(string $content, Category $category): string
    {
        $start = strpos($content, 'data-id="'.$category->id.'"');
        $this->assertNotFalse($start, "La categoría {$category->name} no está en el panel");
        $item = substr($content, $start);

        return substr($item, 0, strpos($item, '</li>'));
    }

    public function test_dropdown_only_for_categories_with_active_subcategories(): void
    {
        $withSubs = $this->makeCategory('Con hijas');
        $this->makeCategory('Hija activa', ['parent_id' => $withSubs->id]);

        $onlyInactive = $this->makeCategory('Solo hijas inactivas');
        $this->makeCategory('Hija inactiva', ['parent_id' => $onlyInactive->id, 'status' => 0]);

        $plain = $this->makeCategory('Sin hijas');

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('sub-cat-menu', $this->navItem($content, $withSubs));
        $this->assertStringNotContainsString('sub-cat-menu', $this->navItem($content, $onlyInactive));
        $this->assertStringNotContainsString('sub-cat-menu', $this->navItem($content, $plain));
    }

    public function test_subcategories_are_not_listed_as_roots(): void
    {
        $root = $this->makeCategory('Raíz');
        $child = $this->makeCategory('Subcategoría', ['parent_id' => $root->id]);

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('data-id="'.$child->id.'"', $content);
    }
}
