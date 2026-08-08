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
        'status', 'commision_rate',
    ];

    public function parent()   { return $this->belongsTo(Category::class, 'parent_id'); }
    public function children() { return $this->hasMany(Category::class, 'parent_id'); }
    public function products() { return $this->hasMany(Product::class); }

    public function scopeActive($query)    { return $query->where('status', 1); }
    public function scopeFeatured($query)  { return $query->where('featured', 1); }
    public function scopeTop($query)       { return $query->where('top', 1); }

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