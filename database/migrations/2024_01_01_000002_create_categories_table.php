<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('icon')->nullable();        // ícono pequeño del menú lateral
            $table->string('banner')->nullable();      // imagen grande para cards
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->tinyInteger('featured')->default(0);
            $table->tinyInteger('top')->default(0);    // para Top 10 Categories
            $table->tinyInteger('digital')->default(0);
            $table->tinyInteger('status')->default(1); // 1=activo, 0=inactivo
            $table->integer('commision_rate')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
