<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'category_id', 'brand_id', 'added_by',
        'unit_price', 'purchase_price', 'discount', 'discount_type',
        'thumbnail', 'photos', 'description', 'short_description',
        'unit', 'min_qty', 'low_stock_qty', 'variant_product',
        'choice_options', 'colors', 'variations', 'shipping_cost',
        'num_of_sale', 'rating', 'reviews_count',
        'featured', 'todays_deal', 'published', 'approved', 'digital',
    ];

    protected $casts = [
        'photos' => 'array', 'choice_options' => 'array',
        'colors' => 'array', 'variations' => 'array',
        'featured' => 'boolean', 'published' => 'boolean', 'approved' => 'boolean',
    ];

    public function category() { return $this->belongsTo(Category::class); }
    public function brand()    { return $this->belongsTo(Brand::class); }
    public function stocks()   { return $this->hasMany(ProductStock::class); }
    public function reviews()  { return $this->hasMany(Review::class); }

    public function scopeActive($query)   { return $query->where('published', 1)->where('approved', 1); }
    public function scopeFeatured($query) { return $query->where('featured', 1); }

    /** Tallas con al menos una fila de stock, en el orden de config/variants.php. */
    public function availableSizes(): array
    {
        $used = $this->stocks->pluck('size')->filter()->unique();

        if ($used->isEmpty()) {
            return [];
        }

        $order = $this->category?->sizeOptions() ?? [];

        return $used->sortBy(fn ($size) => array_search($size, $order, true) === false
            ? PHP_INT_MAX
            : array_search($size, $order, true))->values()->all();
    }

    /** Colores con al menos una fila de stock, en el orden de la paleta. */
    public function availableColors(): array
    {
        $used = $this->stocks->pluck('color')->filter()->unique();

        if ($used->isEmpty()) {
            return [];
        }

        $order = array_keys(config('variants.colors', []));

        return $used->sortBy(fn ($color) => array_search($color, $order, true) === false
            ? PHP_INT_MAX
            : array_search($color, $order, true))->values()->all();
    }

    /** Fila de stock de una combinación concreta, o null si no existe. */
    public function stockFor(?string $size, ?string $color): ?ProductStock
    {
        return $this->stocks->first(fn ($stock) => (string) $stock->size === (string) $size
            && (string) $stock->color === (string) $color);
    }

    /** Si el producto se vende por combinaciones y exige elegir una. */
    public function hasVariants(): bool
    {
        return $this->availableSizes() !== [] || $this->availableColors() !== [];
    }

    public function getDiscountedPriceAttribute(): float
    {
        if ($this->discount <= 0) return (float) $this->unit_price;
        if ($this->discount_type === 'percent')
            return $this->unit_price - ($this->unit_price * $this->discount / 100);
        return max(0, $this->unit_price - $this->discount);
    }
}