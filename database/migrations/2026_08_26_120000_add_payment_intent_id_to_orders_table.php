<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // PaymentIntent vigente del pedido: permite reutilizarlo en cada visita
            // a /checkout en vez de crear uno nuevo (dashboard lleno de "incomplete").
            $table->string('payment_intent_id')->nullable()->index()->after('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_intent_id']);
            $table->dropColumn('payment_intent_id');
        });
    }
};
