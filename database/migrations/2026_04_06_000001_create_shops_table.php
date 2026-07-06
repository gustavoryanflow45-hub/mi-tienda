<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // dueño de la tienda
            $table->string('name');                        // nombre de la tienda
            $table->string('email');                       // email de la tienda
            $table->string('address');                     // dirección
            $table->string('id_front_image');              // foto frontal del ID
            $table->string('id_back_image');               // foto reversa del ID
            $table->tinyInteger('status')->default(0);     // 0=pendiente, 1=aprobado, 2=rechazado
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};