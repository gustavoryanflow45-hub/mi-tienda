<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
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

        // Categorías hijas (para subcategorías en sidebar)
        $childIds = Category::where('parent_id', $category->id)
            ->where('status', 1)
            ->pluck('id')
            ->prepend($category->id); // incluye la propia

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
            'price_asc'  => $query->orderBy('unit_price', 'asc'),
            'price_desc' => $query->orderBy('unit_price', 'desc'),
            'popular'    => $query->orderBy('num_of_sale', 'desc'),
            default      => $query->latest(),   // newest
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
}