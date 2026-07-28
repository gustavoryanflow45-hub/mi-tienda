<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SellerProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (! in_array($user->user_type, ['seller', 'admin'])) {
                abort(403, 'No autorizado.');
            }
            if (! $user->email_verified_at && ! $user->email_verified) {
                return redirect()->route('verification.notice')
                    ->with('warning', 'Debes verificar tu email primero.');
            }

            return $next($request);
        });
    }

    // ── GET /seller/products ─────────────────────────────────────
    public function index(Request $request)
    {
        $query = Product::with(['category', 'stocks'])
            ->where('added_by', Auth::id());

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $products = $query->latest()->paginate(15);

        return view('pages.seller-products', compact('products'));
    }

    // ── GET /seller/products/create ──────────────────────────────
    public function create()
    {
        $categories = Category::where('status', 1)->orderBy('name')->get();
        $brands = Brand::where('status', 1)->orderBy('name')->get();

        return view('pages.seller-product-create', compact('categories', 'brands'));
    }

    // ── POST /seller/products ────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'unit_price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'thumbnail' => 'required|image|mimes:jpg,jpeg,png,webp|max:3072',
            'photos.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            'stocks' => 'required|array|min:1',
            'stocks.*.price' => 'required|numeric|min:0',
            'stocks.*.qty' => 'required|integer|min:0',
        ]);

        // Slug único
        $slug = Str::slug($request->name);
        $count = Product::where('slug', 'like', $slug.'%')->count();
        if ($count > 0) {
            $slug .= '-'.($count + 1);
        }

        // Imagen principal
        $thumbnailPath = $request->file('thumbnail')
            ->store('products/thumbnails', 'public');

        // Imágenes adicionales
        $photosPaths = [];
        if ($request->hasFile('photos')) {
            foreach (array_slice($request->file('photos'), 0, 5) as $photo) {
                $photosPaths[] = $photo->store('products/photos', 'public');
            }
        }

        // Crear producto
        $product = Product::create([
            'name' => $request->name,
            'slug' => $slug,
            'category_id' => $request->category_id,
            'brand_id' => $request->brand_id ?: null,
            'added_by' => Auth::id(),
            'unit_price' => $request->unit_price,
            'purchase_price' => $request->purchase_price ?: null,
            'discount' => $request->discount ?? 0,
            'discount_type' => $request->discount_type ?? 'percent',
            'thumbnail' => $thumbnailPath,
            'photos' => ! empty($photosPaths) ? $photosPaths : null,
            'description' => $request->description,
            'short_description' => $request->short_description,
            'unit' => $request->unit,
            'min_qty' => $request->min_qty ?? 1,
            'low_stock_qty' => $request->low_stock_qty ?? 5,
            'shipping_cost' => $request->shipping_cost ?? 0,
            'is_quantity_multiplied' => $request->is_quantity_multiplied ?? 0,
            'featured' => $request->featured ?? 0,
            'todays_deal' => $request->todays_deal ?? 0,
            'published' => $request->published ?? 1,
            'digital' => $request->digital ?? 0,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
        ]);

        // Crear stocks
        foreach ($request->stocks as $stockData) {
            ProductStock::create([
                'product_id' => $product->id,
                'variant' => $stockData['variant'] ?? null,
                'price' => $stockData['price'],
                'qty' => $stockData['qty'],
                'sku' => $stockData['sku'] ?? null,
            ]);
        }

        return redirect()->route('seller.products.index')
            ->with('success', '¡Producto "'.$product->name.'" creado exitosamente!');
    }

    // ── GET /seller/products/{id}/edit ───────────────────────────
    public function edit($id)
    {
        $product = Product::with('stocks')->findOrFail($id);

        // Solo el dueño (o un admin) puede editar
        if ($product->added_by !== Auth::id() && Auth::user()->user_type !== 'admin') {
            abort(403, 'No autorizado.');
        }

        $categories = Category::where('status', 1)->orderBy('name')->get();
        $brands = Brand::where('status', 1)->orderBy('name')->get();

        return view('pages.seller-product-edit', compact('product', 'categories', 'brands'));
    }

    // ── PUT /seller/products/{id} ────────────────────────────────
    public function update(Request $request, $id)
    {
        $product = Product::with('stocks')->findOrFail($id);

        if ($product->added_by !== Auth::id() && Auth::user()->user_type !== 'admin') {
            abort(403, 'No autorizado.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'unit_price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            'photos.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            'stocks' => 'required|array|min:1',
            'stocks.*.price' => 'required|numeric|min:0',
            'stocks.*.qty' => 'required|integer|min:0',
        ]);

        // Imagen principal: solo se reemplaza si se sube una nueva
        if ($request->hasFile('thumbnail')) {
            $product->thumbnail = $request->file('thumbnail')
                ->store('products/thumbnails', 'public');
        }

        // Imágenes adicionales: si se suben nuevas, reemplazan las existentes
        if ($request->hasFile('photos')) {
            $photosPaths = [];
            foreach (array_slice($request->file('photos'), 0, 5) as $photo) {
                $photosPaths[] = $photo->store('products/photos', 'public');
            }
            $product->photos = $photosPaths;
        }

        $product->fill([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'brand_id' => $request->brand_id ?: null,
            'unit_price' => $request->unit_price,
            'purchase_price' => $request->purchase_price ?: null,
            'discount' => $request->discount ?? 0,
            'discount_type' => $request->discount_type ?? 'percent',
            'description' => $request->description,
            'short_description' => $request->short_description,
            'unit' => $request->unit,
            'min_qty' => $request->min_qty ?? 1,
            'low_stock_qty' => $request->low_stock_qty ?? 5,
            'shipping_cost' => $request->shipping_cost ?? 0,
            'is_quantity_multiplied' => $request->is_quantity_multiplied ?? 0,
            'featured' => $request->featured ?? 0,
            'todays_deal' => $request->todays_deal ?? 0,
            'published' => $request->published ?? 1,
            'digital' => $request->digital ?? 0,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
        ]);
        $product->save();

        // Reemplazar stocks: borrar los antiguos y recrear
        $product->stocks()->delete();
        foreach ($request->stocks as $stockData) {
            ProductStock::create([
                'product_id' => $product->id,
                'variant' => $stockData['variant'] ?? null,
                'price' => $stockData['price'],
                'qty' => $stockData['qty'],
                'sku' => $stockData['sku'] ?? null,
            ]);
        }

        return redirect()->route('seller.products.index')
            ->with('success', '¡Producto "'.$product->name.'" actualizado exitosamente!');
    }

    // ── GET /seller/products/bulk ────────────────────────────────
    public function bulk()
    {
        return view('pages.seller-product-bulk');
    }

    // ── GET /seller/products/bulk/template ──────────────────────
    public function bulkTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="productos_plantilla.csv"',
        ];

        $columns = [
            'name', 'category_id', 'unit_price', 'stock_qty', 'stock_price',
            'brand_id', 'purchase_price', 'discount', 'discount_type',
            'unit', 'shipping_cost', 'short_description', 'description',
            'sku', 'variant', 'published', 'featured',
        ];

        $example = [
            'Camiseta Azul', '1', '29.99', '50', '29.99',
            '', '15.00', '10', 'percent',
            'pieza', '5.00', 'Algodón 100%', 'Descripción completa del producto',
            'CAM-AZU-001', 'Azul-L', '1', '0',
        ];

        $callback = function () use ($columns, $example) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, $example);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── POST /seller/products/bulk ───────────────────────────────
    public function bulkStore(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
        $headers = array_map('trim', fgetcsv($handle));

        $created = 0;
        $errors = [];
        $row = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            if (empty(array_filter($data))) {
                continue;
            }

            $d = array_combine($headers, array_pad($data, count($headers), ''));

            foreach (['name', 'category_id', 'unit_price', 'stock_qty', 'stock_price'] as $req) {
                if (empty($d[$req])) {
                    $errors[] = "Fila {$row}: falta '{$req}'.";

                    continue 2;
                }
            }

            $slug = Str::slug($d['name']);
            $count = Product::where('slug', 'like', $slug.'%')->count();
            if ($count > 0) {
                $slug .= '-'.($count + 1);
            }

            try {
                $product = Product::create([
                    'name' => trim($d['name']),
                    'slug' => $slug,
                    'category_id' => (int) $d['category_id'],
                    'brand_id' => ! empty($d['brand_id']) ? (int) $d['brand_id'] : null,
                    'added_by' => Auth::id(),
                    'unit_price' => (float) $d['unit_price'],
                    'purchase_price' => ! empty($d['purchase_price']) ? (float) $d['purchase_price'] : null,
                    'discount' => ! empty($d['discount']) ? (int) $d['discount'] : 0,
                    'discount_type' => in_array($d['discount_type'] ?? '', ['percent', 'amount']) ? $d['discount_type'] : 'percent',
                    'unit' => $d['unit'] ?? 'pieza',
                    'shipping_cost' => ! empty($d['shipping_cost']) ? (float) $d['shipping_cost'] : 0,
                    'short_description' => $d['short_description'] ?? null,
                    'description' => $d['description'] ?? null,
                    'published' => isset($d['published']) ? (int) $d['published'] : 1,
                    'featured' => isset($d['featured']) ? (int) $d['featured'] : 0,
                ]);

                ProductStock::create([
                    'product_id' => $product->id,
                    'variant' => $d['variant'] ?? null,
                    'price' => (float) $d['stock_price'],
                    'qty' => (int) $d['stock_qty'],
                    'sku' => $d['sku'] ?? null,
                ]);

                $created++;
            } catch (\Exception $e) {
                $errors[] = "Fila {$row}: ".$e->getMessage();
            }
        }

        fclose($handle);

        return redirect()->route('seller.products.index')
            ->with('bulk_success', "Se importaron {$created} producto(s) exitosamente.")
            ->with('bulk_errors', $errors);
    }
}
