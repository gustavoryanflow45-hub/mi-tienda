<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El panel deja de autenticar contra users: los admins pasan a su propia
 * tabla, con su guard 'admin' (config/auth.php).
 *
 * - Cada users.user_type = 'admin' se copia a admins CONSERVANDO el id (y lo
 *   anota en legacy_user_id), así que los valores de las claves foráneas no
 *   cambian: solo pasan a apuntar a admins. La fila de users no se borra.
 * - seller_settlements.admin_id y orders.dispatched_by -> admins.id.
 * - admin_two_factors.user_id pasa a admin_id -> admins.id. La fila (y su
 *   secreto cifrado con APP_KEY) se conserva: no hay que volver a inscribir
 *   el autenticador.
 * - admin_audit_logs gana admin_id: las entradas de un admin pasan ahí y
 *   user_id queda para los usuarios de la tienda (p. ej. la importación CSV
 *   de un vendedor), que también se auditan.
 * - Las notificaciones de esos admins pasan a notifiable_type Admin.
 *
 * down() lo deshace todo vía legacy_user_id. Un admin creado después de
 * migrar no tiene fila en users: en down sus referencias quedan en null y
 * su 2FA se pierde.
 */
return new class extends Migration
{
    private const USER = 'App\\Models\\User';

    private const ADMIN = 'App\\Models\\Admin';

    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            // Cuenta de users de la que salió este admin; solo para trazar y
            // para poder deshacer la migración.
            $table->foreignId('legacy_user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        $admins = DB::table('users')->where('user_type', 'admin')->orderBy('id')->get();

        foreach ($admins as $user) {
            DB::table('admins')->insert([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'password' => $user->password,
                'legacy_user_id' => $user->id,
                'created_at' => $user->created_at,
                'updated_at' => now(),
            ]);
        }

        $this->resyncSequence('admins');

        $ids = $admins->pluck('id');

        // Si una liquidación o un despacho apunta a alguien que ya no es
        // admin, se aborta: no se borra ni se inventa nada.
        $this->assertOnlyAdmins('seller_settlements', 'admin_id', $ids);
        $this->assertOnlyAdmins('orders', 'dispatched_by', $ids);
        $this->assertOnlyAdmins('admin_two_factors', 'user_id', $ids);

        Schema::table('seller_settlements', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->foreign('admin_id')->references('id')->on('admins')->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['dispatched_by']);
            $table->foreign('dispatched_by')->references('id')->on('admins')->nullOnDelete();
        });

        Schema::table('admin_two_factors', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
        });
        Schema::table('admin_two_factors', function (Blueprint $table) {
            $table->renameColumn('user_id', 'admin_id');
        });
        Schema::table('admin_two_factors', function (Blueprint $table) {
            $table->unique('admin_id');
            $table->foreign('admin_id')->references('id')->on('admins')->cascadeOnDelete();
        });

        Schema::table('admin_audit_logs', function (Blueprint $table) {
            $table->foreignId('admin_id')->nullable()->after('id')->constrained('admins')->nullOnDelete();
        });

        if ($ids->isNotEmpty()) {
            DB::table('admin_audit_logs')->whereIn('user_id', $ids)
                ->update(['admin_id' => DB::raw('user_id'), 'user_id' => null]);

            DB::table('notifications')->where('notifiable_type', self::USER)->whereIn('notifiable_id', $ids)
                ->update(['notifiable_type' => self::ADMIN]);
        }
    }

    public function down(): void
    {
        // admins.id -> users.id (null si el admin no salió de users).
        $map = DB::table('admins')->pluck('legacy_user_id', 'id');

        Schema::table('seller_settlements', fn (Blueprint $table) => $table->dropForeign(['admin_id']));
        Schema::table('orders', fn (Blueprint $table) => $table->dropForeign(['dispatched_by']));
        Schema::table('admin_two_factors', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->dropUnique(['admin_id']);
        });
        Schema::table('admin_audit_logs', fn (Blueprint $table) => $table->dropForeign(['admin_id']));

        foreach ($map as $adminId => $userId) {
            DB::table('seller_settlements')->where('admin_id', $adminId)->update(['admin_id' => $userId]);
            DB::table('orders')->where('dispatched_by', $adminId)->update(['dispatched_by' => $userId]);
            DB::table('admin_audit_logs')->where('admin_id', $adminId)->update(['user_id' => $userId]);

            if ($userId === null) {
                DB::table('admin_two_factors')->where('admin_id', $adminId)->delete();
                DB::table('notifications')->where('notifiable_type', self::ADMIN)->where('notifiable_id', $adminId)->delete();
            } else {
                DB::table('admin_two_factors')->where('admin_id', $adminId)->update(['admin_id' => $userId]);
                DB::table('notifications')->where('notifiable_type', self::ADMIN)->where('notifiable_id', $adminId)
                    ->update(['notifiable_type' => self::USER, 'notifiable_id' => $userId]);
            }
        }

        Schema::table('admin_audit_logs', fn (Blueprint $table) => $table->dropColumn('admin_id'));

        Schema::table('admin_two_factors', function (Blueprint $table) {
            $table->renameColumn('admin_id', 'user_id');
        });
        Schema::table('admin_two_factors', function (Blueprint $table) {
            $table->unique('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('dispatched_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('seller_settlements', function (Blueprint $table) {
            $table->foreign('admin_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::dropIfExists('admins');
    }

    private function assertOnlyAdmins(string $table, string $column, $adminIds): void
    {
        $orphans = DB::table($table)->whereNotNull($column)->whereNotIn($column, $adminIds)
            ->distinct()->pluck($column);

        if ($orphans->isNotEmpty()) {
            throw new RuntimeException("{$table}.{$column} apunta a usuarios que no son admin ({$orphans->implode(', ')}). Revísalo antes de migrar.");
        }
    }

    /** Tras insertar ids a mano, PostgreSQL debe seguir desde el mayor. */
    private function resyncSequence(string $table): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE((SELECT MAX(id) FROM {$table}), 1), (SELECT MAX(id) FROM {$table}) IS NOT NULL)");
    }
};
