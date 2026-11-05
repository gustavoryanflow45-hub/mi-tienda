<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Árbol de categorías activas, en el orden del menú de la tienda
 * (config/categories.php). Los productos de una categoría se piden con
 * GET /api/v1/products?category={slug}, que incluye sus subcategorías.
 */
class CategoryController extends Controller
{
    // ── GET /api/v1/categories ───────────────────────────────────
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::active()
            ->whereNull('parent_id')
            ->orderBy('order')
            ->orderBy('id')
            ->with(['children' => fn ($query) => $query->active()->orderBy('order')->orderBy('id')])
            ->get();

        return CategoryResource::collection($categories);
    }

    // ── GET /api/v1/categories/{slug} ────────────────────────────
    public function show(string $slug): CategoryResource
    {
        $category = Category::active()
            ->where('slug', $slug)
            ->with([
                'children' => fn ($query) => $query->active()->orderBy('order')->orderBy('id'),
                'parent',
            ])
            ->firstOrFail();

        return new CategoryResource($category);
    }
}
