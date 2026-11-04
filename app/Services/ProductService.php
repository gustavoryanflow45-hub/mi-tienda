<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Alta y edición de un producto con sus combinaciones talla × color.
 *
 * Lo usan el panel del vendedor (SellerProductController) y la moderación
 * del admin (Admin\ProductController), así que las reglas de las variantes
 * no pueden divergir entre los dos. Quién puede editar qué lo decide
 * ProductPolicy; aquí solo se valida y se guarda.
 *
 * La importación CSV (SellerProductController@bulkStore) tiene sus propias
 * reglas por fila y solo comparte uniqueSlug().
 */
class ProductService
{
    public function __construct(
        private ImageOptimizer $images,
        private AuditLog $audit,
    ) {}

    /**
     * Valida el formulario (campos, imágenes y combinaciones). $input es lo
     * que manda el formulario, archivos incluidos ($request->all()).
     *
     * @throws ValidationException
     */
    public function validate(array $input, bool $creating): array
    {
        $maxKb = config('images.max_upload_kb');

        $data = Validator::make($input, [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'unit_price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'thumbnail' => ($creating ? 'required' : 'nullable').'|image|mimes:jpg,jpeg,png,webp|max:'.$maxKb,
            'photos.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:'.$maxKb,
            'stocks' => 'required|array|min:1',
            'stocks.*.price' => 'required|numeric|min:0',
            'stocks.*.qty' => 'required|integer|min:0',
        ])->validate();

        $this->validateVariants($input);

        return $data;
    }

    public function create(User $owner, array $input, ?UploadedFile $thumbnail, array $photos = []): Product
    {
        $product = Product::create($this->attributes($input) + [
            'slug' => self::uniqueSlug($input['name']),
            'added_by' => $owner->id,
            // Imagen principal reducida y en WebP: ver config/images.php
            'thumbnail' => $thumbnail ? $this->images->store($thumbnail, 'products/thumbnails', 'product_thumbnail') : null,
            'photos' => $this->storePhotos($photos) ?: null,
        ]);

        $this->syncStocks($product, $input['stocks']);

        return $product;
    }

    /**
     * Las imágenes solo se reemplazan si llegan nuevas; las fotos nuevas
     * sustituyen a todas las anteriores. No cambia el dueño (added_by) ni
     * el slug.
     */
    public function update(Product $product, array $input, ?UploadedFile $thumbnail, array $photos = []): Product
    {
        if ($thumbnail) {
            $product->thumbnail = $this->images->store($thumbnail, 'products/thumbnails', 'product_thumbnail');
        }

        if ($photos) {
            $product->photos = $this->storePhotos($photos);
        }

        $product->fill($this->attributes($input))->save();
        $this->syncStocks($product, $input['stocks']);

        return $product;
    }

    /**
     * Moderación del panel: aprueba (true) o desactiva (false) el producto.
     * Desactivado (products.approved = 0) desaparece de la tienda aunque su
     * vendedor lo tenga publicado (Product::scopeActive), y el vendedor lo ve
     * marcado en su listado. No hace nada si ya estaba así.
     *
     * @return bool false si no cambió nada.
     */
    public function setApproved(Product $product, bool $approved, Admin $admin): bool
    {
        if ((bool) $product->approved === $approved) {
            return false;
        }

        $product->forceFill(['approved' => $approved ? 1 : 0])->save();
        $this->audit->record($approved ? 'product.approved' : 'product.disabled', $product, ['seller_id' => $product->added_by], $admin);

        return true;
    }

    /** "zapatillas-runner", o "zapatillas-runner-3" si ya hay dos. */
    public static function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $count = Product::where('slug', 'like', $slug.'%')->count();

        return $count > 0 ? $slug.'-'.($count + 1) : $slug;
    }

    /** Campos del formulario comunes a alta y edición. */
    protected function attributes(array $input): array
    {
        return [
            'name' => $input['name'],
            'category_id' => $input['category_id'],
            'brand_id' => ($input['brand_id'] ?? null) ?: null,
            'unit_price' => $input['unit_price'],
            'purchase_price' => ($input['purchase_price'] ?? null) ?: null,
            'discount' => $input['discount'] ?? 0,
            'discount_type' => $input['discount_type'] ?? 'percent',
            'description' => $input['description'] ?? null,
            'short_description' => $input['short_description'] ?? null,
            'unit' => $input['unit'],
            'variant_type' => ($input['variant_type'] ?? null) ?: null,
            'min_qty' => $input['min_qty'] ?? 1,
            'low_stock_qty' => $input['low_stock_qty'] ?? 5,
            'shipping_cost' => $input['shipping_cost'] ?? 0,
            'is_quantity_multiplied' => $input['is_quantity_multiplied'] ?? 0,
            'featured' => $input['featured'] ?? 0,
            'todays_deal' => $input['todays_deal'] ?? 0,
            'published' => $input['published'] ?? 1,
            'digital' => $input['digital'] ?? 0,
            'meta_title' => $input['meta_title'] ?? null,
            'meta_description' => $input['meta_description'] ?? null,
        ];
    }

    /** Hasta 5 fotos adicionales, reducidas. */
    protected function storePhotos(array $photos): array
    {
        return array_map(
            fn (UploadedFile $photo) => $this->images->store($photo, 'products/photos', 'product_photo'),
            array_slice(array_values($photos), 0, 5),
        );
    }

    /**
     * Valida las combinaciones talla/color contra lo que admite el producto.
     *
     * La UI ya oculta las tallas en categorías que no las usan, pero la regla
     * se aplica aquí para que no dependa del formulario: un POST directo con
     * talla en una categoría "solo color" tiene que fallar.
     */
    protected function validateVariants(array $input): void
    {
        $category = Category::find($input['category_id'] ?? null);

        if (! $category) {
            return; // la regla exists: de validate() ya se encargó
        }

        Validator::make($input, [
            'variant_type' => ['nullable', Rule::in(array_keys(config('variants.types', [])))],
        ], [
            'variant_type.in' => 'Tipo de talla no válido.',
        ])->validate();

        // Se resuelven las tallas sobre un producto en memoria, el mismo objeto
        // que usa la vista: manda el variant_type declarado y, si no viene, el
        // de la categoría. Así la regla del servidor no puede divergir de las
        // tallas que el formulario acaba de pintar.
        $resolver = new Product(['variant_type' => $input['variant_type'] ?? null]);
        $resolver->setRelation('category', $category);

        $allowedSizes = $resolver->sizeOptions();
        $allowedColors = array_keys(config('variants.colors', []));
        $stocks = $input['stocks'] ?? [];
        $rules = [];

        foreach (array_keys($stocks) as $i) {
            $rules["stocks.$i.color"] = ['nullable', Rule::in($allowedColors)];

            $rules["stocks.$i.size"] = $resolver->usesSizes()
                ? ['nullable', Rule::in($allowedSizes)]
                : ['nullable', 'prohibited'];
        }

        Validator::make($input, $rules, [
            'stocks.*.size.prohibited' => "Este producto no maneja tallas. La categoría \"{$category->name}\" no las trae por defecto: elige un tipo de talla para ofrecerlas, o deja solo el color.",
            'stocks.*.size.in' => 'Talla no válida para el tipo de talla elegido.',
            'stocks.*.color.in' => 'Color no válido.',
        ])->validate();

        // Dos filas con la misma combinación dejarían el stock ambiguo: al
        // resolver la variante en el carrito ganaría una de las dos al azar.
        $combos = collect($stocks)
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
    protected function syncStocks(Product $product, array $stocks): void
    {
        $product->stocks()->delete();

        foreach ($stocks as $stockData) {
            ProductStock::create([
                'product_id' => $product->id,
                'size' => filled($stockData['size'] ?? null) ? $stockData['size'] : null,
                'color' => filled($stockData['color'] ?? null) ? $stockData['color'] : null,
                'price' => $stockData['price'],
                'qty' => $stockData['qty'],
                'sku' => $stockData['sku'] ?? null,
            ]);
        }

        // variant_product refleja si el producto se vende por combinaciones,
        // que es lo que el detalle usa para exigir una selección.
        $product->update([
            'variant_product' => $product->load('stocks')->hasVariants() ? 1 : 0,
        ]);
    }
}
