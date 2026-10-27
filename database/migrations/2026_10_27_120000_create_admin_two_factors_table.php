<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Segundo factor (TOTP) de los admin. Va en su propia tabla, no en
        // users: el secreto no se mezcla con el modelo que ve toda la app y
        // users.$fillable no cambia. Que un admin haya pasado el 2FA se
        // guarda en la sesión, nunca aquí.
        Schema::create('admin_two_factors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            // Cifrado con APP_KEY (cast 'encrypted'): rotar la clave obliga a
            // reinscribir a los admin (php artisan admin:2fa-invite --reset).
            $table->text('secret');
            // Hashes (bcrypt) de los códigos de recuperación aún sin usar.
            $table->json('recovery_codes');
            // Último paso de 30 s aceptado: un código no vale dos veces.
            $table->unsignedBigInteger('last_used_timestep')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_two_factors');
    }
};
