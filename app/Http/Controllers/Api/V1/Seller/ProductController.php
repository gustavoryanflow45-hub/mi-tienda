<?php

namespace App\Http\Controllers\Api\V1\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SellerProductResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Productos del vendedor del token, con el mismo ProductService que el
 * formulario web y la moderación del panel: una combinación que la web
 * rechaza tampoco entra desde la app. Solo los suyos (added_by); el de otro
 * vendedor es 404.
 *
 * Alta y edición van en multipart/form-data por las imágenes (thumbnail,
 * photos[]); la edición, como PHP no lee archivos en un PUT, se manda como
 * POST con _method=PUT (o como PUT en JSON si no hay imágenes nuevas).
 */
class ProductController extends Controller
{
    /** Campos del formulario que la app puede mandar (los de partials/product-form). */
    private const FIELDS = [
        'name', 'category_id', 'brand_id', 'unit_price', 'purchase_price', 'discount', 'discount_type',
        'description', 'short_description', 'unit', 'variant_type', 'min_qty', 'low_stock_qty',
        'shipping_cost', 'published', 'featured', 'todays_deal',
    ];

    /** Los que el formulario no muestra; en una edición se conservan. */
    private const KEPT = ['digital', 'is_quantity_multiplied', 'meta_title', 'meta_description'];

    public function __construct(private ProductService $products) {}

    // ── GET /api/v1/seller/products/options ──────────────────────
    // Lo que necesita el formulario: categorías, marcas, tipos de talla y colores.
    public function options(): JsonResponse
    {
        return response()->json(['data' => [
            'categories' => Category::active()->orderBy('order')->orderBy('id')->get()
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'parent_id' => $category->parent_id,
                    // Tipo de talla por defecto de sus productos (cada producto puede declarar otro).
                    'variant_type' => array_key_exists((string) $category->variant_type, config('variants.types', []))
                        ? $category->variant_type
                        : config('variants.default_type', 'none'),
                ])->values(),
            'brands' => Brand::where('status', 1)->orderBy('name')->get(['id', 'name']),
            'variant_types' => collect(config('variants.types', []))->map(fn (array $type, string $key) => [
                'key' => $key,
                'label' => __($type['label'] ?? $key),
                'size_label' => isset($type['size_label']) ? __($type['size_label']) : null,
                'sizes' => $type['sizes'] ?? [],
            ])->values(),
            'colors' => collect(config('variants.colors', []))->map(fn (array $color, string $key) => [
                'key' => $key,
                'label' => __($color['label'] ?? $key),
                'hex' => $color['hex'] ?? null,
            ])->values(),
            'max_upload_kb' => (int) config('images.max_upload_kb'),
            'max_photos' => 5,
        ]]);
    }

    // ── GET /api/v1/seller/products ──────────────────────────────
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $products = Product::with(['category', 'stocks'])
            ->where('added_by', $request->user()->id)
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->search($term))
            ->latest()
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return SellerProductResource::collection($products);
    }

    // ── GET /api/v1/seller/products/{id} ─────────────────────────
    public function show(Request $request, int $product): SellerProductResource
    {
        return new SellerProductResource($this->own($request, $product));
    }

    // ── POST /api/v1/seller/products ─────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $input = $this->input($request);
        $this->products->validate($input + $request->allFiles(), creating: true);

        $product = $this->products->create(
            $request->user(),
            $input,
            $request->file('thumbnail'),
            $request->file('photos', []),
        );

        // refresh(): approved y los demás valores por defecto los pone la base.
        return (new SellerProductResource($product->refresh()->load(['category', 'stocks'])))
            ->response()
            ->setStatusCode(201);
    }

    // ── PUT /api/v1/seller/products/{id} ─────────────────────────
    // Lo que no se mande se conserva. `stocks`, si llega, reemplaza todas las
    // combinaciones; fotos nuevas sustituyen a todas las anteriores.
    public function update(Request $request, int $product): SellerProductResource
    {
        $model = $this->own($request, $product);
        $this->authorize('update', $model);

        $input = $this->input($request, $model);
        $this->products->validate($input + $request->allFiles(), creating: false);
        $this->products->update($model, $input, $request->file('thumbnail'), $request->file('photos', []));

        return new SellerProductResource($model->refresh()->load(['category', 'stocks']));
    }

    // ── POST /api/v1/seller/products/{id}/toggle-published ───────
    public function togglePublished(Request $request, int $product): SellerProductResource
    {
        $model = $this->own($request, $product);
        Gate::authorize('togglePublished', $model);

        $model->forceFill(['published' => ! $model->published])->save();

        return new SellerProductResource($model);
    }

    private function own(Request $request, int $id): Product
    {
        return Product::with(['category', 'stocks'])->where('added_by', $request->user()->id)->findOrFail($id);
    }

    /**
     * Solo los campos del formulario, con los booleanos como 0/1. En una
     * edición, lo que no llega sale del producto (stocks incluidos), para que
     * un campo omitido no vuelva a su valor por defecto
     * (ProductService::attributes()); un null explícito sí lo vacía.
     */
    private function input(Request $request, ?Product $product = null): array
    {
        $request->validate([
            'published' => ['nullable', 'boolean'],
            'featured' => ['nullable', 'boolean'],
            'todays_deal' => ['nullable', 'boolean'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'in:percent,amount'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'min_qty' => ['nullable', 'integer', 'min:1'],
            'low_stock_qty' => ['nullable', 'integer', 'min:0'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'description' => ['nullable', 'string', 'max:65000'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'stocks.*.sku' => ['nullable', 'string', 'max:100'],
        ]);

        $input = $request->only([...self::FIELDS, 'stocks']);

        foreach (['published', 'featured', 'todays_deal'] as $flag) {
            if (array_key_exists($flag, $input) && $input[$flag] !== null) {
                $input[$flag] = $request->boolean($flag) ? 1 : 0;
            }
        }

        if ($product) {
            $current = collect($product->only([...self::FIELDS, ...self::KEPT]))
                ->map(fn ($value) => is_bool($value) ? (int) $value : $value)
                ->all();
            $current['stocks'] = $product->stocks
                ->map(fn ($stock) => $stock->only(['size', 'color', 'price', 'qty', 'sku']))
                ->all();
            $input = array_merge($current, $input);
        }

        return $input;
    }
}
