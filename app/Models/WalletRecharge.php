<?php
 
// ══════════════════════════════════════════
//  app/Models/WalletRecharge.php
// ══════════════════════════════════════════
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class WalletRecharge extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'payment_proof',
        'note',
        'approval',
    ];
 
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}