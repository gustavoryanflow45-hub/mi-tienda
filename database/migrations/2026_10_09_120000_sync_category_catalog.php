<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pasa las categorías al catálogo de config/categories.php: las 10 viejas en
 * inglés se renombran en su sitio y se crean las que faltan.
 *
 * Sobre una tabla vacía (migrate:fresh) no hace nada a propósito: si creara
 * aquí las 35 categorías, legacy:restore saltaría la tabla por tener filas y
 * los productos heredados quedarían colgando de ids que ya no son los suyos.
 * En ese caso es el propio legacy:restore (o el DatabaseSeeder) quien llama
 * a Category::syncCatalog() después de traer las filas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('categories')->exists()) {
            Category::syncCatalog();
        }
    }

    public function down(): void
    {
        // Renombrar de vuelta no tiene sentido: los nombres viejos siguen en
        // la base heredada, y las categorías nuevas pueden tener productos.
    }
};
