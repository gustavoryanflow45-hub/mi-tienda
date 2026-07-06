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
}