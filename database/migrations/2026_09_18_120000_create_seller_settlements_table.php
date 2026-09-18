<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cada liquidación cierra un ciclo de ventas del vendedor: guarda
        // cuánto vendió, cuánto retuvo el marketplace y cuánto se le paga.
        Schema::create('seller_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('total_sales', 12, 2);
            $table->decimal('commission_rate', 5, 4);
            $table->decimal('commission', 12, 2);
            $table->decimal('net_amount', 12, 2);
            $table->unsignedInteger('lines_count')->default(0);
            $table->timestamp('settled_at');
            $table->timestamps();
        });

        // Una línea liquidada apunta a su liquidación; las que siguen en null
        // son las que suman en el panel del vendedor.
        Schema::table('order_details', function (Blueprint $table) {
            $table->foreignId('settlement_id')->nullable()->after('payment_status')
                ->constrained('seller_settlements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('settlement_id');
        });

        Schema::dropIfExists('seller_settlements');
    }
};
