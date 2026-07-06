<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::active()->orderBy('order')->get();
        return view('pages.brands', compact('brands'));
    }

    public function show(string $slug)
    {
        $brand    = Brand::where('slug', $slug)->firstOrFail();
        $products = Product::active()->where('brand_id', $brand->id)->paginate(24);
        return view('pages.brand-detail', compact('brand', 'products'));
    }
}
