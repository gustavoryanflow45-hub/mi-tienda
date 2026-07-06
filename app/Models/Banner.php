<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = ['title', 'image', 'link', 'type', 'order', 'status'];

    public function scopeActive($query) { return $query->where('status', 1); }
}