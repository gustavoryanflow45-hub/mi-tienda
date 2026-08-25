<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Índice de todas las categorías raíz con sus subcategorías.
     * GET /categories
     */
    public function index()
    {
        $categories = Category::active()
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->where('status', 1)->orderBy('order')])
            ->orderBy('order')
            ->get();

        // Un solo conteo agrupado en vez de una consulta por categoría.
        $counts = Product::where('published', 1)
            ->selectRaw('category_id, count(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        // Cada raíz suma lo suyo más lo de sus hijas, igual que hace show().
        $categories->each(function (Category $category) use ($counts) {
            $category->product_count = $category->children
                ->pluck('id')
                ->prepend($category->id)
                ->sum(fn ($id) => $counts[$id] ?? 0);
        });

        return view('pages.categories', compact('categories'));
    }

    /**
     * Muestra los productos de una categoría por su slug.
     * GET /category/{slug}
     */
    public function show(Request $request, string $slug)
    {
        // Buscar la categoría activa
        $category = Category::where('slug', $slug)
            ->where('status', 1)
            ->firstOrFail();

        // Esta categoría y sus subcategorías activas
        $childIds = $category->selfAndChildrenIds();

        // Query base: productos de esta cat o sus hijas
        $query = Product::with(['category', 'stocks'])
            ->whereIn('category_id', $childIds)
            ->where('published', 1);

        // ── Filtro precio ────────────────────────────────────────
        if ($request->filled('min_price')) {
            $query->where('unit_price', '>=', (float) $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('unit_price', '<=', (float) $request->max_price);
        }

        // ── Filtro marca ─────────────────────────────────────────
        if ($request->filled('brand')) {
            $query->where('brand_id', (int) $request->brand);
        }

        // ── Ordenamiento ─────────────────────────────────────────
        match ($request->get('sort', 'newest')) {
            'price_asc' => $query->orderBy('unit_price', 'asc'),
            'price_desc' => $query->orderBy('unit_price', 'desc'),
            'popular' => $query->orderBy('num_of_sale', 'desc'),
            default => $query->latest(),   // newest
        };

        $products = $query->paginate(12)->withQueryString();

        // Precio máximo para el slider
        $maxPrice = Product::whereIn('category_id', $childIds)
            ->where('published', 1)
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
