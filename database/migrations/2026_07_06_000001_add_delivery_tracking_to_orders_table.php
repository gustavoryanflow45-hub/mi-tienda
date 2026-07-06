<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('paid_at');
            $table->timestamp('warehouse_at')->nullable()->after('confirmed_at');
            $table->timestamp('dispatched_at')->nullable()->after('warehouse_at');
            $table->timestamp('delivered_at')->nullable()->after('dispatched_at');
            $table->foreignId('dispatched_by')->nullable()->after('delivered_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dispatched_by');
            $table->dropColumn(['confirmed_at', 'warehouse_at', 'dispatched_at', 'delivered_at']);
        });
    }
};
