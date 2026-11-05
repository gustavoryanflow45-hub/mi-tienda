<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comisión de red de cada solicitud de la billetera (config wallet.app_network_fee).
 * Hoy solo la cobra la app: una recarga de X acredita X − fee al aprobarse y
 * un retiro de X descuenta X del saldo y se transfieren X − fee. Las de la
 * web, y todas las anteriores, quedan en 0 (sin comisión).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['wallet_recharges', 'wallet_withdrawals'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('fee', 10, 2)->default(0);
            });
        }
    }

    public function down(): void
    {
        foreach (['wallet_recharges', 'wallet_withdrawals'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('fee');
            });
        }
    }
};
