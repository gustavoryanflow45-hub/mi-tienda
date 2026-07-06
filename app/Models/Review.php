<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'product_id', 'user_id', 'order_detail_id',
        'rating', 'comment', 'photos', 'status',
    ];
    protected $casts = ['photos' => 'array'];
    public function product() { return $this->belongsTo(Product::class); }
    public function user()    { return $this->belongsTo(User::class); }
    public function scopeApproved($query) { return $query->where('status', 1); }
}