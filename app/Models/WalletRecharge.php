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
        // Comisión de red (solo la app la cobra; 0 en la web).
        'fee',
        // Red y hash de la transferencia: el panel los muestra para verificarla.
        'network',
        'transaction_id',
        'payment_proof',
        'note',
        'approval',
    ];
 
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Lo que se acredita al aprobarla: el monto menos la comisión de red. */
    public function netAmount(): float
    {
        return round((float) $this->amount - (float) $this->fee, 2);
    }
}
