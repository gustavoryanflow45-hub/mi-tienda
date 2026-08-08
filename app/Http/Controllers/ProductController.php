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

    // ── POST /product/variant_price ──────────────────────────────
    // Devuelve precio y stock de una variante seleccionada (AJAX)
    public function variantPrice(Request $request)
    {
        $product = Product::with('stocks')->findOrFail($request->id);

        // Buscar stock por variante
        $variant = $request->get('variant');
        $stock   = $product->stocks->firstWhere('variant', $variant)
                ?? $product->stocks->first();

        if (!$stock) {
            return response()->json([
                'price'    => number_format($product->unit_price, 2),
                'quantity' => 0,
                'in_stock' => 0,
                'digital'  => $product->digital,
                'max_limit'=> 0,
            ]);
        }

        // Calcular precio con descuento
        $price = $stock->price;
        if ($product->discount > 0) {
            $price = $product->discount_type === 'percent'
                ? $stock->price * (1 - $product->discount / 100)
                : $stock->price - $product->discount;
        }

        return response()->json([
            'price'    => '$' . number_format($price, 2),
            'quantity' => $stock->qty,
            'in_stock' => $stock->qty > 0 ? 1 : 0,
            'digital'  => $product->digital,
            'max_limit'=> $stock->qty,
        ]);
    }
}