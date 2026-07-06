<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('temp_user_id')->nullable();    // para usuarios no logueados
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_stock_id')->nullable()->constrained('product_stocks')->nullOnDelete();
            $table->string('variation')->nullable();
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
