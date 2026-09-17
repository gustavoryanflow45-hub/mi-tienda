<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'parent_id', 'icon', 'banner',
        'description', 'order', 'featured', 'top', 'digital',
        'status', 'commision_rate', 'variant_type',
    ];

    public function parent()   { return $this->belongsTo(Category::class, 'parent_id'); }
    public function children() { return $this->hasMany(Category::class, 'parent_id'); }
    public function products() { return $this->hasMany(Product::class); }

    public function scopeActive($query)    { return $query->where('status', 1); }
    public function scopeFeatured($query)  { return $query->where('featured', 1); }
    public function scopeTop($query)       { return $query->where('top', 1); }

    /**
     * Configuración de variantes de la categoría, según config/variants.php.
     * Una categoría con un tipo desconocido cae al tipo por defecto, así que
     * un dato mal escrito degrada a "solo color" en vez de romper la vista.
     */
    public function variantConfig(): array
    {
        $types   = config('variants.types', []);
        $default = config('variants.default_type', 'none');

        return $types[$this->variant_type]
            ?? $types[$default]
            ?? ['label' => 'Sin tallas', 'sizes' => []];
    }

    /** Tallas disponibles para los productos de esta categoría. */
    public function sizeOptions(): array
    {
        return $this->variantConfig()['sizes'] ?? [];
    }

    /** Si sus productos se venden por talla además de por color. */
    public function usesSizes(): bool
    {
        return count($this->sizeOptions()) > 0;
    }

    /** Etiqueta del selector de talla ("Talla", "Talla US", "Cintura"). */
    public function sizeLabel(): string
    {
        return $this->variantConfig()['size_label'] ?? 'Talla';
    }

    /**
     * Asigna a cada categoría el variant_type que le corresponde según
     * config('variants.category_types'). Devuelve cuántas filas cambiaron.
     *
     * Vive aquí, y no dentro de la migración que creó la columna, porque hay
     * que poder reaplicarlo: variant_type nace en 'none' y cada repoblado del
     * catálogo (legacy:restore) vuelve a traerlo así. La migración original
     * hizo el UPDATE una sola vez, y como en esta base el esquema se migró
     * antes de que existieran las filas, lo hizo sobre una tabla vacía: todas
     * las categorías quedaron en 'none' y ninguna llegó a ofrecer tallas.
     *
     * Es idempotente: solo toca las categorías cuyo tipo no coincide ya.
     */
    public static function applyConfiguredVariantTypes(): int
    {
        $changed = 0;

        foreach (config('variants.category_types', []) as $slug => $type) {
            $changed += static::where('slug', $slug)
                ->where(function ($query) use ($type) {
                    $query->where('variant_type', '!=', $type)->orWhereNull('variant_type');
                })
                ->update(['variant_type' => $type]);
        }

        return $changed;
    }

    /**
     * IDs de esta categoría más los de sus subcategorías activas, para
     * listar productos que cuelguen de cualquiera de ellas.
     *
     * Solo baja un nivel, que es la profundidad que maneja el árbol actual.
     */
    public function selfAndChildrenIds(): \Illuminate\Support\Collection
    {
        return static::active()
            ->where('parent_id', $this->id)
            ->pluck('id')
            ->prepend($this->id);
    }
}