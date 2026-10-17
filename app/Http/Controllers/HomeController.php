<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        // Solo raíces: sus subcategorías salen en el desplegable de cada una,
        // que el panel lateral monta únicamente cuando la cuenta es > 0.
        $categories      = Category::active()->whereNull('parent_id')->orderBy('order')
            ->withCount(['children as active_children_count' => fn ($q) => $q->where('status', 1)])
            ->get();
        $top_categories  = Category::active()->orderBy('order')->take(10)->get();
        $top_brands      = Brand::active()->orderBy('order')->take(10)->get();
        $banners         = Banner::active()->where('type', 'slider')->orderBy('order')->get();
        $new_products    = Product::active()->latest()->take(12)->get();

        // Productos destacados para la sección del home
        $featured_products = Product::active()
            ->where('featured', 1)
            ->latest()
            ->take(12)
            ->get();

        // Secciones que antes se cargaban por AJAX tras el primer render:
        // se traen aquí para que las imágenes vengan en el HTML inicial
        // y no haya que esperar a un round-trip extra para verlas.
        $best_selling_products = Product::active()->orderBy('num_of_sale', 'desc')->take(12)->get();
        $home_categories       = Category::active()->whereNull('parent_id')->take(6)->get();
        $best_seller_products  = Product::active()->orderBy('rating', 'desc')->take(12)->get();

        // Una sección por cada categoría marcada como destacada
        $featured_categories = Category::active()->featured()->orderBy('order')->get()
            ->map(fn ($category) => [$category, $this->productsForCategory($category)]);

        return view('home', compact(
            'categories',
            'top_categories',
            'top_brands',
            'banners',
            'new_products',
            'featured_products',
            'best_selling_products',
            'home_categories',
            'best_seller_products',
            'featured_categories'
        ));
    }

    public function section(Request $request, $section)
    {
        switch ($section) {
            case 'featured':
                $products = Product::active()->where('featured', 1)->take(12)->get();
                return view('partials.home-sections.featured', compact('products'));
            case 'best_selling':
                $products = Product::active()->orderBy('num_of_sale', 'desc')->take(12)->get();
                return view('partials.home-sections.best_selling', compact('products'));
            case 'home_categories':
                $categories = Category::active()->whereNull('parent_id')->take(6)->get();
                return view('partials.home-sections.home_categories', compact('categories'));
            case 'best_sellers':
                $products = Product::active()->orderBy('rating', 'desc')->take(12)->get();
                return view('partials.home-sections.best_sellers', compact('products'));
            default:
                // Cualquier otro valor se interpreta como el slug de una
                // categoría destacada. Los nombres fijos de arriba tienen
                // precedencia, así que una categoría no puede llamarse igual.
                $category = Category::active()->featured()->where('slug', $section)->first();

                if (! $category) {
                    return response()->json(['error' => 'Section not found'], 404);
                }

                $products = $this->productsForCategory($category);

                return view('partials.home-sections.category', compact('products', 'category'));
        }
    }

    /**
     * Productos de una categoría destacada para su sección del home,
     * incluyendo los que cuelgan de sus subcategorías activas.
     */
    private function productsForCategory(Category $category): \Illuminate\Support\Collection
    {
        return Product::active()
            ->whereIn('category_id', $category->selfAndChildrenIds())
            ->latest()
            ->take(12)
            ->get();
    }

    // ── POST /seller/products/{id}/toggle-published ──────────────
    public function togglePublished(Request $request, int $id)
    {
        $user    = Auth::user();
        $product = Product::findOrFail($id);

        if ($user->user_type !== 'admin' && $product->added_by !== $user->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $product->published = $product->published ? 0 : 1;
        $product->save();

        return response()->json([
            'published' => $product->published,
            'message'   => $product->published ? 'Producto publicado' : 'Producto despublicado',
        ]);
    }
}