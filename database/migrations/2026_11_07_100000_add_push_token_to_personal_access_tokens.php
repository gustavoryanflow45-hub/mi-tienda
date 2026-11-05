<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Token de notificaciones push (FCM) de cada dispositivo de la app, en la
 * fila de su sesión: cerrar sesión, revocar el dispositivo o que el token
 * caduque lo borra con ella, así que nunca se le manda un push a un móvil
 * que ya no tiene la cuenta abierta. El envío todavía no existe; esto deja
 * el registro listo (ver User::routeNotificationForFcm()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->string('push_token', 255)->nullable()->index();
            $table->string('push_platform', 10)->nullable();
            $table->timestamp('push_token_updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex(['push_token']);
            $table->dropColumn(['push_token', 'push_platform', 'push_token_updated_at']);
        });
    }
};
