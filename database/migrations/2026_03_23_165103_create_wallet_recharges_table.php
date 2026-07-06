<?php
 
// ══════════════════════════════════════════════════════
//  MIGRACIÓN 1 – wallet_recharges
//  Crea el archivo en:
//  database/migrations/xxxx_xx_xx_create_wallet_recharges_table.php
// ══════════════════════════════════════════════════════
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_recharges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('network')->nullable();           // BEP20 | TRC20 | ERC20
            $table->string('transaction_id')->nullable();    // ID de transacción
            $table->string('payment_proof')->nullable();     // ruta del comprobante
            $table->tinyInteger('approval')->default(0);     // 0=pendiente 1=aprobado -1=rechazado
            $table->timestamps();
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('wallet_recharges');
    }
};