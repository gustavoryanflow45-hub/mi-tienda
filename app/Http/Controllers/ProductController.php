<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    // ── GET /products ────────────────────────────────────────────
    // Lista pública de todos los productos
    public function index(Request $request)
    {
        $query = Product::with(['category', 'stocks', 'brand'])
            ->where('published', 1);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->latest()->paginate(16)->withQueryString();

        return view('pages.products', compact('products'));
    }

    // ── GET /product/{slug} ──────────────────────────────────────
    // Página pública de detalle de un producto
    public function show(string $slug)
    {
        $product = Product::with([
            'category',
            'brand',
            'stocks',
            'reviews',
        ])
            ->where('slug', $slug)
            ->where('published', 1)
            ->firstOrFail();

        // Productos relacionados (misma categoría, excluye el actual)
        $related = Product::with('stocks')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('published', 1)
            ->limit(6)
            ->get();

        // Fotos adicionales (el modelo ya castea la columna a array)
        $photos = $product->photos ?: [];

        return view('pages.product-detail', compact('product', 'related', 'photos'));
    }
}
