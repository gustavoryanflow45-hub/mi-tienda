<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProductIndexRequest;
use App\Http\Resources\V1\ProductDetailResource;
use App\Http\Resources\V1\ProductResource;
use App\Services\CatalogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Catálogo público: solo productos a la venta (publicados por su vendedor
 * y no desactivados por el panel). Un producto que no lo está es un 404,
 * igual que en la web.
 */
class ProductController extends Controller
{
    public function __construct(private CatalogService $catalog) {}

    // ── GET /api/v1/products ─────────────────────────────────────
    public function index(ProductIndexRequest $request): AnonymousResourceCollection
    {
        $products = $this->catalog
            ->filter($this->catalog->products(), $request->validated())
            ->paginate($request->perPage())
            ->withQueryString();

        return ProductResource::collection($products);
    }

    // ── GET /api/v1/products/{slug} ──────────────────────────────
    // También acepta el id numérico (lo que guardan carrito y pedidos).
    public function show(string $product): ProductDetailResource
    {
        $model = $this->catalog->products()
            ->with('seller.shop')
            ->when(ctype_digit($product),
                fn ($query) => $query->whereKey((int) $product),
                fn ($query) => $query->where('slug', $product))
            ->firstOrFail();

        $model->setRelation('related', $this->catalog->related($model));

        return new ProductDetailResource($model);
    }
}
