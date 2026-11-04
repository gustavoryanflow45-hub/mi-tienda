<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Services\AuditLog;
use App\Services\ProductService;
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
            if (! $user->isSeller()) {
                abort(403, __('No autorizado.'));
            }
            if (! $user->isVerified()) {
                return redirect()->route('verification.notice')
                    ->with('warning', __('Debes verificar tu correo electrónico antes de usar el panel de vendedor.'));
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
            $query->search($request->search);
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
    public function store(Request $request, ProductService $products)
    {
        $products->validate($request->all(), creating: true);

        $product = $products->create(
            Auth::user(),
            $request->all(),
            $request->file('thumbnail'),
            $request->file('photos', []),
        );

        return redirect()->route('seller.products.index')
            ->with('success', __('¡Producto ":name" creado exitosamente!', ['name' => $product->name]));
    }

    // ── GET /seller/products/{id}/edit ───────────────────────────
    public function edit($id)
    {
        $product = Product::with('stocks')->findOrFail($id);

        // Solo el dueño: ProductPolicy
        $this->authorize('update', $product);

        $categories = Category::where('status', 1)->orderBy('name')->get();
        $brands = Brand::where('status', 1)->orderBy('name')->get();

        return view('pages.seller-product-edit', compact('product', 'categories', 'brands'));
    }

    // ── PUT /seller/products/{id} ────────────────────────────────
    public function update(Request $request, $id, ProductService $products)
    {
        $product = Product::with('stocks')->findOrFail($id);

        $this->authorize('update', $product);

        $products->validate($request->all(), creating: false);
        $products->update($product, $request->all(), $request->file('thumbnail'), $request->file('photos', []));

        return redirect()->route('seller.products.index')
            ->with('success', __('¡Producto ":name" actualizado exitosamente!', ['name' => $product->name]));
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
            'sku', 'variant_type', 'size', 'color', 'published', 'featured',
        ];

        $example = [
            'Camiseta Azul', '1', '29.99', '50', '29.99',
            '', '15.00', '10', 'percent',
            'pieza', '5.00', 'Algodón 100%', 'Descripción completa del producto',
            'CAM-AZU-001', 'apparel', 'L', 'azul', '1', '0',
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
    public function bulkStore(Request $request, AuditLog $audit)
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

            $slug = ProductService::uniqueSlug($d['name']);

            try {
                // Antes del create: un tipo inventado dejaría el producto
                // guardado con un variant_type que no resuelve a nada.
                if (filled($d['variant_type'] ?? null)
                    && ! array_key_exists(trim($d['variant_type']), config('variants.types', []))) {
                    throw new \RuntimeException('variant_type "'.trim($d['variant_type']).'" no existe.');
                }

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
                    'variant_type' => filled($d['variant_type'] ?? null) ? trim($d['variant_type']) : null,
                    'short_description' => $d['short_description'] ?? null,
                    'description' => $d['description'] ?? null,
                    'published' => isset($d['published']) ? (int) $d['published'] : 1,
                    'featured' => isset($d['featured']) ? (int) $d['featured'] : 0,
                ]);

                $size = filled($d['size'] ?? null) ? trim($d['size']) : null;
                $color = filled($d['color'] ?? null) ? Str::lower(trim($d['color'])) : null;

                // Mismas reglas que el formulario: la talla se valida contra
                // el tipo del producto (su variant_type o, si no lo declara,
                // el de la categoría), y el color contra la paleta.
                if ($size !== null && ! in_array($size, $product->sizeOptions(), true)) {
                    throw new \RuntimeException("talla \"{$size}\" no válida para este producto.");
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

        // Solo recuentos: ni el archivo ni sus filas.
        $audit->record('products.csv_imported', null, ['created' => $created, 'errors' => count($errors)]);

        return redirect()->route('seller.products.index')
            ->with('bulk_success', __('Se importaron :count producto(s) exitosamente.', ['count' => $created]))
            ->with('bulk_errors', $errors);
    }
}
