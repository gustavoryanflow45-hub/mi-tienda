<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $seller = User::factory()->create(['user_type' => 'seller']);
        $category = Category::create(['name' => 'Calzado', 'slug' => 'calzado', 'status' => 1]);

        foreach ([
            ['Zapatillas de running', 'Ligeras y con buena amortiguación', 1],
            ['Botas de montaña', 'Impermeables, para senderismo', 1],
            ['Sandalias 100% cuero', 'Para el verano', 1],
            ['Zapatillas sin publicar', '', 0],
        ] as [$name, $description, $published]) {
            Product::create([
                'name' => $name,
                'slug' => str($name)->slug(),
                'description' => $description,
                'category_id' => $category->id,
                'added_by' => $seller->id,
                'unit_price' => 50,
                'published' => $published,
                'approved' => 1,
            ]);
        }
    }

    /** Lo que envía el JS de la cabecera: el texto va en `search`. */
    protected function suggest(string $text)
    {
        return $this->post(route('search.ajax'), ['search' => $text]);
    }

    public function test_suggestions_are_filtered_by_what_the_user_typed(): void
    {
        // Antes leía `keyword`, que el JS no manda: buscaba vacío y devolvía todo.
        $this->suggest('botas')
            ->assertOk()
            ->assertSee('Botas de montaña')
            ->assertDontSee('Zapatillas de running');
    }

    public function test_suggestions_come_rendered_as_html_with_links(): void
    {
        // El JS las pinta con .html(): un JSON no se veía.
        $response = $this->suggest('zapatillas')->assertOk();

        $this->assertStringNotContainsString('application/json', (string) $response->headers->get('Content-Type'));
        $response->assertSee(route('products.show', 'zapatillas-de-running'), false)
            ->assertSee(route('search', ['keyword' => 'zapatillas']), false);
    }

    public function test_suggestions_answer_zero_when_nothing_matches(): void
    {
        // '0' es la señal del JS para "No encontramos nada para…".
        $this->suggest('xyzxyz')->assertOk()->assertContent('0');
        $this->suggest('   ')->assertOk()->assertContent('0');
    }

    public function test_unpublished_products_are_not_suggested(): void
    {
        $this->suggest('zapatillas')->assertSee('Zapatillas de running')->assertDontSee('Zapatillas sin publicar');
    }

    public function test_search_ignores_case(): void
    {
        // En PostgreSQL `LIKE` distingue mayúsculas: "zapa" no encontraba
        // "Zapatillas". SQLite no las distingue, así que aquí esto documenta
        // el contrato más que reproducir el fallo; la consulta usa LOWER() en
        // los dos lados para que valga igual en ambos motores.
        $this->suggest('ZAPATILLAS de RUNNING')->assertSee('Zapatillas de running');
        $this->get(route('search', ['keyword' => 'botas']))->assertSee('Botas de montaña');
    }

    public function test_wildcards_typed_by_the_user_are_literal(): void
    {
        // Sin escapar, "%" valía "cualquier cosa" y "_" "cualquier carácter".
        $this->suggest('%')->assertSee('Sandalias 100% cuero')->assertDontSee('Botas de montaña');
        $this->suggest('_')->assertContent('0');
    }

    public function test_the_results_page_also_searches_descriptions(): void
    {
        $this->get(route('search', ['keyword' => 'IMPERMEABLES']))
            ->assertOk()
            ->assertSee('Botas de montaña')
            ->assertDontSee('Zapatillas de running');
    }

    public function test_the_products_page_search_box_uses_the_same_rules(): void
    {
        $this->get(route('products.index', ['search' => 'BOTAS']))
            ->assertOk()
            ->assertSee('Botas de montaña')
            ->assertDontSee('Zapatillas de running');
    }
}
