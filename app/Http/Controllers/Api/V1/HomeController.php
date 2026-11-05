<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BannerResource;
use App\Http\Resources\V1\BrandResource;
use App\Http\Resources\V1\ProductResource;
use App\Models\Banner;
use App\Models\Brand;
use App\Services\CatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Portada de la app en una sola petición, con las mismas secciones que la
 * portada web (Shop\HomeController): banners, destacados, novedades y más
 * vendidos.
 */
class HomeController extends Controller
{
    public const SECTION_SIZE = 12;

    // ── GET /api/v1/home ─────────────────────────────────────────
    public function index(CatalogService $catalog): JsonResponse
    {
        return response()->json(['data' => [
            'banners' => BannerResource::collection(Banner::active()->where('type', 'slider')->orderBy('order')->get()),
            'featured' => ProductResource::collection($catalog->products()->featured()->latest()->orderByDesc('id')->limit(self::SECTION_SIZE)->get()),
            'new' => ProductResource::collection($catalog->products()->latest()->orderByDesc('id')->limit(self::SECTION_SIZE)->get()),
            'best_selling' => ProductResource::collection($catalog->products()->orderByDesc('num_of_sale')->orderByDesc('id')->limit(self::SECTION_SIZE)->get()),
        ]]);
    }

    // ── GET /api/v1/brands ───────────────────────────────────────
    public function brands(): AnonymousResourceCollection
    {
        return BrandResource::collection(Brand::active()->orderBy('order')->orderBy('name')->get());
    }
}
