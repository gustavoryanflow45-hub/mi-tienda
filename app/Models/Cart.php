<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'user_id', 'temp_user_id', 'product_id', 'product_stock_id',
        'variation', 'quantity', 'price', 'tax', 'shipping_cost',
    ];
    public function product() { return $this->belongsTo(Product::class); }
    public function user()    { return $this->belongsTo(User::class); }
}