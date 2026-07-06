<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStock extends Model
{
    protected $fillable = ['product_id', 'variant', 'price', 'qty', 'sku'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}