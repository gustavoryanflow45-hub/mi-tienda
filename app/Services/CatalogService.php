<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

/**
 * Consultas del catálogo público: siempre productos a la venta
 * (Product::scopeActive: publicados y no desactivados por el panel).
 *
 * La usan la página de categoría de la tienda (Shop\CategoryController@show)
 * y la API (Api\V1\ProductController), para que un filtro dé lo mismo en la
 * web y en la app.
 */
class CatalogService
{
    public const SORTS = ['newest', 'price_asc', 'price_desc', 'popular', 'rating'];

    /** Productos a la venta con lo que necesitan las tarjetas. */
    public function products(): Builder
    {
        return Product::query()->active()->with(['category', 'brand', 'stocks']);
    }

    /**
     * Aplica los filtros admitidos. Claves (todas opcionales):
     *   q          texto (Product::scopeSearch, sin distinguir mayúsculas)
     *   category   Category, o el slug de una activa: incluye sus subcategorías
     *   brand      id de marca
     *   min_price / max_price   sobre el precio del producto (unit_price)
     *   featured   true = solo destacados
     *   sort       newest (por defecto) | price_asc | price_desc | popular | rating
     */
    public function filter(Builder $query, array $filters): Builder
    {
        if (filled($filters['q'] ?? null)) {
            $query->search((string) $filters['q']);
        }

        if ($category = $this->category($filters['category'] ?? null)) {
            $query->whereIn('category_id', $category->selfAndChildrenIds());
        }

        if (filled($filters['brand'] ?? null)) {
            $query->where('brand_id', (int) $filters['brand']);
        }

        if (filled($filters['min_price'] ?? null)) {
            $query->where('unit_price', '>=', (float) $filters['min_price']);
        }

        if (filled($filters['max_price'] ?? null)) {
            $query->where('unit_price', '<=', (float) $filters['max_price']);
        }

        if (filter_var($filters['featured'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $query->featured();
        }

        match ($filters['sort'] ?? 'newest') {
            'price_asc' => $query->orderBy('unit_price', 'asc'),
            'price_desc' => $query->orderBy('unit_price', 'desc'),
            'popular' => $query->orderBy('num_of_sale', 'desc'),
            'rating' => $query->orderBy('rating', 'desc'),
            default => $query->latest(),
        };

        // Desempate estable: la paginación no repite ni salta productos.
        return $query->orderBy('id', 'desc');
    }

    /** Un slug que no sea de una categoría activa no filtra: no existe. */
    protected function category(Category|string|null $category): ?Category
    {
        if ($category instanceof Category || $category === null || $category === '') {
            return $category ?: null;
        }

        return Category::active()->where('slug', $category)->first();
    }

    /** Otros productos a la venta de la misma categoría. */
    public function related(Product $product, int $limit = 6)
    {
        return $this->products()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->latest()
            ->limit($limit)
            ->get();
    }
}
