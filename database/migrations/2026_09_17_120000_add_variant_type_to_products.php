<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Override del variant_type de la categoría. Nullable = "lo que
            // diga la categoría", que es el comportamiento que ya había.
            //
            // Hace falta porque el catálogo tiene categorías mixtas: las
            // zapatillas cuelgan de "Sports & outdoor" junto a carpas y
            // bicicletas, así que el tipo no puede decidirse por categoría
            // sin ofrecer tallas de calzado a media tienda.
            $table->string('variant_type')->nullable()->after('variant_product');
        });

        // Repara el dato que la migración de tallas nunca llegó a escribir: su
        // UPDATE corrió cuando categories todavía estaba vacía (el esquema se
        // migró antes de repoblar con legacy:restore), así que las 10
        // categorías quedaron en 'none' y el formulario del vendedor nunca
        // ofreció tallas para nada.
        Category::applyConfiguredVariantTypes();
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('variant_type');
        });
    }
};
