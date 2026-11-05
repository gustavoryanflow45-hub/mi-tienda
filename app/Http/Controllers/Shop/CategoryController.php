<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\CatalogService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /** Productos que se ven en el panel de cada categoría antes de "Ver todos". */
    public const PANEL_PRODUCTS = 11;

    /** Pestaña virtual con los productos destacados, primera del menú. */
    public const FEATURED_TAB = 'destacados';

    /**
     * Página de categorías: menú lateral con las raíces y, a la derecha, la
     * cuadrícula de subcategorías y productos de la elegida.
     * GET /categories?c={slug}
     *
     * Se pintan todos los paneles de una vez y el cambio de pestaña es local,
     * sin round trip; ?c= solo decide cuál abre, así que cada pestaña es
     * también un enlace que funciona sin JavaScript.
     */
    public function index(Request $request)
    {
        $panelProducts = fn ($query) => $query->active()
            ->latest()
            ->limit(self::PANEL_PRODUCTS);

        $categories = Category::active()
            ->whereNull('parent_id')
            ->with([
                'children' => fn ($q) => $q->where('status', 1)->orderBy('order'),
                'products' => $panelProducts,
                'children.products' => $panelProducts,
            ])
            ->orderBy('order')
            ->get();

        // Un solo conteo agrupado en vez de una consulta por categoría.
        $counts = Product::active()
            ->selectRaw('category_id, count(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        // Cada raíz suma lo suyo más lo de sus hijas, igual que hace show().
        $categories->each(function (Category $category) use ($counts) {
            $category->product_count = $category->children
                ->pluck('id')
                ->prepend($category->id)
                ->sum(fn ($id) => $counts[$id] ?? 0);

            $category->panel_products = $category->products
                ->concat($category->children->flatMap->products)
                ->sortByDesc('created_at')
                ->take(self::PANEL_PRODUCTS)
                ->values();
        });

        // Destacados: los marcados como tales; si no hay ninguno, los más
        // vendidos, para que la primera pestaña nunca abra vacía.
        $featured = Product::active()->featured()
            ->orderByDesc('num_of_sale')->latest()
            ->limit(self::PANEL_PRODUCTS)->get();

        if ($featured->isEmpty()) {
            $featured = Product::active()
                ->orderByDesc('num_of_sale')->latest()
                ->limit(self::PANEL_PRODUCTS)->get();
        }

        $featuredTab = self::FEATURED_TAB;
        $selected = $categories->contains('slug', $request->query('c'))
            ? $request->query('c')
            : $featuredTab;

        return view('pages.categories', compact('categories', 'featured', 'featuredTab', 'selected'));
    }

    /**
     * Muestra los productos de una categoría por su slug.
     * GET /category/{slug}
     */
    public function show(Request $request, string $slug, CatalogService $catalog)
    {
        // Buscar la categoría activa
        $category = Category::where('slug', $slug)
            ->where('status', 1)
            ->firstOrFail();

        // Esta categoría y sus subcategorías activas
        $childIds = $category->selfAndChildrenIds();

        // Mismos filtros que la API (CatalogService): esta categoría y sus
        // subcategorías, marca, rango de precio y orden.
        $query = $catalog->filter($catalog->products(), [
            'category' => $category,
            'brand' => $request->input('brand'),
            'min_price' => $request->input('min_price'),
            'max_price' => $request->input('max_price'),
            'sort' => $request->get('sort', 'newest'),
        ]);

        $products = $query->paginate(12)->withQueryString();

        // Precio máximo para el slider
        $maxPrice = Product::whereIn('category_id', $childIds)
            ->active()
            ->max('unit_price') ?? 100;

        // Todas las categorías activas para el sidebar
        $allCategories = Category::where('status', 1)
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();

        return view('pages.category-products', compact(
            'category',
            'products',
            'allCategories',
            'maxPrice',
        ));
    }

    /**
     * Devuelve el submenú (subcategorías) de una categoría para el
     * panel lateral de categorías del home, al pasar el mouse por encima.
     * POST /category/nav-element-list
     */
    public function navElement(Request $request)
    {
        $category = Category::where('id', (int) $request->input('id'))
            ->where('status', 1)
            ->first();

        if (! $category) {
            return response('');
        }

        $subCategories = Category::where('parent_id', $category->id)
            ->where('status', 1)
            ->orderBy('order')
            ->get();

        return view('partials.category-nav-submenu', compact('category', 'subCategories'));
    }
}
