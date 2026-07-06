<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'logo', 'order', 'top', 'status'];

    public function products() { return $this->hasMany(Product::class); }

    public function scopeActive($query) { return $query->where('status', 1); }
    public function scopeTop($query)    { return $query->where('top', 1); }
}