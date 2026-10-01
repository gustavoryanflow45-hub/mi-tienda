<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_withdrawals', function (Blueprint $table) {
            // Retiro cubierto por ventas ya liquidadas por el admin: se aprueba
            // solo al crearse, sin segunda revisión. Lo que viene de recargas
            // sigue pasando por /admin/wallet.
            $table->boolean('from_settlement')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_withdrawals', function (Blueprint $table) {
            $table->dropColumn('from_settlement');
        });
    }
};
