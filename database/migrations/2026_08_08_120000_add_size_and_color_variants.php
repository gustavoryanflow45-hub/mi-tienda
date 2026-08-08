<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Apunta a una clave de config('variants.types'). String libre y no
            // enum para poder añadir rubros (gorras, guantes...) sin migrar.
            $table->string('variant_type')->default('none')->after('digital');
        });

        Schema::table('product_stocks', function (Blueprint $table) {
            // Antes toda la variante cabía en un solo string ('M', 'negro'),
            // lo que hacía imposible tener stock por combinación talla+color.
            $table->string('size')->nullable()->after('product_id');
            $table->string('color')->nullable()->after('size');

            $table->index(['product_id', 'size', 'color']);
        });

        // Tipos de variante de las categorías que ya existen.
        $types = [
            'footwear' => ['zapatos'],
            'apparel'  => ['womens-fashion', 'mens-fashion'],
        ];

        foreach ($types as $type => $slugs) {
            DB::table('categories')->whereIn('slug', $slugs)->update(['variant_type' => $type]);
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('variant_type');
        });

        Schema::table('product_stocks', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'size', 'color']);
            $table->dropColumn(['size', 'color']);
        });
    }
};
