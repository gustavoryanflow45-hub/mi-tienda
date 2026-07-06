<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword', '');

        $products = Product::active()
            ->where(function($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            })
            ->paginate(20);

        $categories = Category::active()->whereNull('parent_id')->get();

        return view('search', compact('products', 'keyword', 'categories'));
    }

    public function ajax(Request $request)
    {
        $keyword = $request->get('keyword', '');

        $products = Product::active()
            ->where('name', 'like', "%{$keyword}%")
            ->take(8)
            ->get()
            ->map(function($p) {
                return [
                    'id'        => $p->id,
                    'name'      => $p->name,
                    'slug'      => $p->slug,
                    'price'     => number_format($p->unit_price, 2),
                    'thumbnail' => $p->thumbnail
                        ? asset('uploads/' . $p->thumbnail)
                        : 'https://via.placeholder.com/60x60/f8f9fa/679941?text=P',
                ];
            });

        return response()->json($products);
    }
}