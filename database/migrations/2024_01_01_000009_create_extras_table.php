<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lista de deseos
        if (!Schema::hasTable('wishlists')) {
            Schema::create('wishlists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'product_id']);
            });
        }

        // Reseñas de productos
        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('order_detail_id')->nullable()->constrained('order_details')->nullOnDelete();
                $table->decimal('rating', 3, 1);              // 1.0 - 5.0
                $table->text('comment')->nullable();
                $table->string('photos')->nullable();         // JSON de fotos de la reseña
                $table->tinyInteger('status')->default(1);    // 1=aprobada
                $table->timestamps();
            });
        }

        // Direcciones de envío guardadas
        if (!Schema::hasTable('addresses')) {
            Schema::create('addresses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('full_name');
                $table->string('phone');
                $table->string('email')->nullable();
                $table->string('address');
                $table->string('country')->nullable();
                $table->string('state')->nullable();
                $table->string('city')->nullable();
                $table->string('postal_code')->nullable();
                $table->tinyInteger('is_default')->default(0);
                $table->timestamps();
            });
        }

        // Suscriptores del newsletter
        if (!Schema::hasTable('subscribers')) {
            Schema::create('subscribers', function (Blueprint $table) {
                $table->id();
                $table->string('email')->unique();
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        // Comparaciones de productos
        if (!Schema::hasTable('compare_lists')) {
            Schema::create('compare_lists', function (Blueprint $table) {
                $table->id();
                $table->string('session_id')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->timestamps();
            });
        }

        // Cupones de descuento
        if (!Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->enum('type', ['percent', 'amount'])->default('percent');
                $table->decimal('discount', 10, 2);
                $table->decimal('minimum_purchase', 10, 2)->default(0);
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->integer('max_usage')->nullable();     // null = ilimitado
                $table->integer('used_times')->default(0);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        // Historial de wallets
        if (!Schema::hasTable('wallet_histories')) {
            Schema::create('wallet_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('amount', 10, 2);
                $table->enum('type', ['credit', 'debit']);
                $table->string('details')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_histories');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('compare_lists');
        Schema::dropIfExists('subscribers');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('wishlists');
    }
};