<?php

namespace App\Http\Requests\Api\V1;

use App\Services\CatalogService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * GET /api/v1/products — filtros del catálogo (CatalogService::filter) y
 * paginación. Una categoría o marca que no existe es un 422, no un listado
 * sin filtrar.
 */
class ProductIndexRequest extends FormRequest
{
    public const MAX_PER_PAGE = 50;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', Rule::exists('categories', 'slug')->where('status', 1)],
            'brand' => ['nullable', 'integer', Rule::exists('brands', 'id')->where('status', 1)],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', Rule::when($this->filled('min_price'), 'gte:min_price')],
            'featured' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(CatalogService::SORTS)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 20);
    }
}
