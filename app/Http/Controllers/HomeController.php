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
        $categories      = Category::active()->orderBy('order')->get();
        $top_categories  = Category::active()->orderBy('order')->take(10)->get();
        $top_brands      = Brand::active()->orderBy('order')->take(10)->get();
        $banners         = Banner::active()->orderBy('order')->get();
        $new_products    = Product::active()->latest()->take(12)->get();
        $promo_banners_1 = Banner::active()->where('type', 'promo_1')->get();

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

        // Sección destacada de Zapatos
        $zapatos_category = Category::active()->where('slug', 'zapatos')->first();
        $zapatos_products = $zapatos_category
            ? Product::active()->where('category_id', $zapatos_category->id)->latest()->take(12)->get()
            : collect();

        // Si el usuario es admin o seller, cargar sus productos para gestión
        $my_products = null;
        if (Auth::check() && in_array(Auth::user()->user_type, ['admin', 'seller'])) {
            $my_products = Product::with(['category'])
                ->where('added_by', Auth::id())
                ->where('published', 1)
                ->latest()
                ->take(20)
                ->get();
        }

        return view('home', compact(
            'categories',
            'top_categories',
            'top_brands',
            'banners',
            'new_products',
            'promo_banners_1',
            'featured_products',
            'best_selling_products',
            'home_categories',
            'best_seller_products',
            'zapatos_category',
            'zapatos_products',
            'my_products'
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
            case 'zapatos':
                $category = Category::active()->where('slug', 'zapatos')->first();
                $products = $category
                    ? Product::active()->where('category_id', $category->id)->latest()->take(12)->get()
                    : collect();
                return view('partials.home-sections.zapatos', compact('products', 'category'));
            default:
                return response()->json(['error' => 'Section not found'], 404);
        }
    }

    // ── POST /seller/products/{id}/toggle-featured ───────────────
    // Admin y seller pueden destacar/quitar destaque de sus productos
    public function toggleFeatured(Request $request, int $id)
    {
        $user    = Auth::user();
        $product = Product::findOrFail($id);

        // Solo el dueño del producto o un admin puede modificarlo
        if ($user->user_type !== 'admin' && $product->added_by !== $user->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $product->featured = $product->featured ? 0 : 1;
        $product->save();

        return response()->json([
            'featured' => $product->featured,
            'message'  => $product->featured
                ? 'Producto destacado en el home'
                : 'Producto quitado del home',
        ]);
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