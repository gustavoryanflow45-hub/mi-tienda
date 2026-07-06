<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    protected $fillable = ['email', 'status'];
    public function scopeActive($query) { return $query->where('status', 1); }
}