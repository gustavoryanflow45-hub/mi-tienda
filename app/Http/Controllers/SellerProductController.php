<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            if (! $user->isVerified()) {
                return redirect()->route('verification.notice')
                    ->with('warning', 'Debes verificar tu correo electrónico antes de usar el panel de vendedor.');
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

        $this->validateVariants($request);

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

        $this->syncStocks($product, $request->stocks);

        return redirect()->route('seller.products.index')
            ->with('success', '¡Producto "'.$product->name.'" creado exitosamente!');
    }

    /**
     * Valida las combinaciones talla/color contra lo que admite la categoría.
     *
     * La UI ya oculta las tallas en categorías que no las usan, pero la regla
     * se aplica aquí para que no dependa del formulario: un POST directo con
     * talla en una categoría "solo color" tiene que fallar.
     */
    private function validateVariants(Request $request): void
    {
        $category = Category::find($request->category_id);

        if (! $category) {
            return; // la regla exists: del validate() ya se encargó
        }

        $allowedSizes  = $category->sizeOptions();
        $allowedColors = array_keys(config('variants.colors', []));
        $rules         = [];

        foreach (array_keys($request->stocks ?? []) as $i) {
            $rules["stocks.$i.color"] = ['nullable', Rule::in($allowedColors)];

            $rules["stocks.$i.size"] = $category->usesSizes()
                ? ['nullable', Rule::in($allowedSizes)]
                : ['nullable', 'prohibited'];
        }

        $request->validate($rules, [
            'stocks.*.size.prohibited' => "La categoría \"{$category->name}\" no maneja tallas, solo color.",
            'stocks.*.size.in'         => 'Talla no válida para esta categoría.',
            'stocks.*.color.in'        => 'Color no válido.',
        ]);

        // Dos filas con la misma combinación dejarían el stock ambiguo: al
        // resolver la variante en el carrito ganaría una de las dos al azar.
        $combos = collect($request->stocks)
            ->map(fn ($s) => ProductStock::buildVariant($s['size'] ?? null, $s['color'] ?? null));

        if ($combos->count() !== $combos->unique()->count()) {
            $repetida = $combos->duplicates()->first();

            throw ValidationException::withMessages([
                'stocks' => 'Hay filas de stock repetidas para la misma combinación'
                    .($repetida ? " (\"{$repetida}\")" : '').'. Deja una sola por talla y color.',
            ]);
        }
    }

    /** Reemplaza las filas de stock del producto por las del formulario. */
    private function syncStocks(Product $product, array $stocks): void
    {
        $product->stocks()->delete();

        foreach ($stocks as $stockData) {
            ProductStock::create([
                'product_id' => $product->id,
                'size'       => filled($stockData['size'] ?? null) ? $stockData['size'] : null,
                'color'      => filled($stockData['color'] ?? null) ? $stockData['color'] : null,
                'price'      => $stockData['price'],
                'qty'        => $stockData['qty'],
                'sku'        => $stockData['sku'] ?? null,
            ]);
        }

        // variant_product refleja si el producto se vende por combinaciones,
        // que es lo que el detalle usa para exigir una selección.
        $product->update([
            'variant_product' => $product->load('stocks')->hasVariants() ? 1 : 0,
        ]);
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

        $this->validateVariants($request);

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

        $this->syncStocks($product, $request->stocks);

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
            'sku', 'size', 'color', 'published', 'featured',
        ];

        $example = [
            'Camiseta Azul', '1', '29.99', '50', '29.99',
            '', '15.00', '10', 'percent',
            'pieza', '5.00', 'Algodón 100%', 'Descripción completa del producto',
            'CAM-AZU-001', 'L', 'azul', '1', '0',
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

                $size  = filled($d['size'] ?? null) ? trim($d['size']) : null;
                $color = filled($d['color'] ?? null) ? Str::lower(trim($d['color'])) : null;

                // Mismas reglas que el formulario: sin tallas donde la
                // categoría no las usa, y colores dentro de la paleta.
                if ($size !== null && ! in_array($size, $product->category?->sizeOptions() ?? [], true)) {
                    throw new \RuntimeException("talla \"{$size}\" no válida para la categoría.");
                }
                if ($color !== null && ! array_key_exists($color, config('variants.colors', []))) {
                    throw new \RuntimeException("color \"{$color}\" no está en la paleta.");
                }

                ProductStock::create([
                    'product_id' => $product->id,
                    'size' => $size,
                    'color' => $color,
                    'price' => (float) $d['stock_price'],
                    'qty' => (int) $d['stock_qty'],
                    'sku' => $d['sku'] ?? null,
                ]);

                $product->update(['variant_product' => ($size || $color) ? 1 : 0]);

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
