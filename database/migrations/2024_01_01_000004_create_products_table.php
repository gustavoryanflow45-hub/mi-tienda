<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete(); // vendedor

            // Precios
            $table->decimal('unit_price', 10, 2);           // precio base
            $table->decimal('purchase_price', 10, 2)->nullable(); // precio de compra
            $table->integer('discount')->default(0);        // porcentaje de descuento
            $table->enum('discount_type', ['percent', 'amount'])->default('percent');

            // Imágenes
            $table->string('thumbnail')->nullable();
            $table->text('photos')->nullable();             // JSON de imágenes adicionales

            // Detalles
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('unit')->nullable();             // kg, pieza, etc.
            $table->integer('min_qty')->default(1);
            $table->integer('low_stock_qty')->default(5);

            // Inventario y variantes
            $table->tinyInteger('variant_product')->default(0);
            $table->text('choice_options')->nullable();     // JSON de opciones (talla, color, etc.)
            $table->text('colors')->nullable();             // JSON de colores
            $table->text('variations')->nullable();         // JSON de variaciones con stock

            // Envío
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->tinyInteger('is_quantity_multiplied')->default(0);

            // Estadísticas
            $table->unsignedInteger('num_of_sale')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);

            // Flags
            $table->tinyInteger('featured')->default(0);
            $table->tinyInteger('todays_deal')->default(0);
            $table->tinyInteger('published')->default(1);
            $table->tinyInteger('approved')->default(1);
            $table->tinyInteger('digital')->default(0);     // producto digital

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_image')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
