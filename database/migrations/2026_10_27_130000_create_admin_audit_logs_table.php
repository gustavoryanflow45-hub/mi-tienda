<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Registro de acciones sensibles (ver App\Services\AuditLog). Pese al
        // nombre no es solo del admin: también guarda lo que hace un vendedor
        // cuando la acción lo merece (importación CSV). Solo inserciones:
        // el modelo rechaza updates y deletes.
        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();
            // Quién: null si lo hizo la consola o un intento sin sesión
            // (login fallido). Se conserva aunque la cuenta se borre.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64)->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            // Solo campos elegidos en cada llamada (importes, recuentos);
            // nunca tarjetas, comprobantes, contraseñas ni códigos.
            $table->json('meta')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
    }
};
