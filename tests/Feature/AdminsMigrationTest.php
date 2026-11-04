<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * La migración que saca a los admins de users (2026_11_04_120000): copia
 * conservando el id, repunta las claves foráneas sin cambiar valores, no
 * borra la fila de users y se deshace con down().
 */
class AdminsMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function migration(): object
    {
        return require database_path('migrations/2026_11_04_120000_create_admins_table.php');
    }

    /** Estado previo a la migración: un admin en users con su historial. */
    protected function seedLegacyAdmin(): array
    {
        $this->migration()->down();

        $admin = User::factory()->create(['user_type' => 'admin', 'email' => 'jefe@tienda.test']);
        $seller = User::factory()->create(['user_type' => 'seller']);
        $secret = encrypt('JBSWY3DPEHPK3PXP', false);

        DB::table('seller_settlements')->insert([
            'seller_id' => $seller->id, 'admin_id' => $admin->id, 'total_sales' => 100,
            'commission_rate' => 0.25, 'commission' => 25, 'net_amount' => 75, 'settled_at' => now(),
        ]);
        DB::table('orders')->insert(['code' => 'ORD-1', 'dispatched_by' => $admin->id]);
        DB::table('admin_two_factors')->insert([
            'user_id' => $admin->id, 'secret' => $secret, 'recovery_codes' => '[]', 'confirmed_at' => now(),
        ]);
        DB::table('admin_audit_logs')->insert([
            ['user_id' => $admin->id, 'action' => 'shop.approved'],
            ['user_id' => $seller->id, 'action' => 'products.csv_imported'],
        ]);
        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(), 'type' => 'X', 'notifiable_type' => User::class,
            'notifiable_id' => $admin->id, 'data' => '{}',
        ]);

        return [$admin, $seller, $secret];
    }

    public function test_up_moves_the_admin_keeping_ids_secret_and_history(): void
    {
        [$admin, $seller, $secret] = $this->seedLegacyAdmin();

        $this->migration()->up();

        $moved = Admin::findOrFail($admin->id);
        $this->assertSame('jefe@tienda.test', $moved->email);
        $this->assertSame($admin->id, (int) $moved->legacy_user_id);
        // Misma contraseña: el hash se copia tal cual.
        $this->assertSame($admin->password, $moved->password);
        // La fila de users se conserva.
        $this->assertNotNull(User::find($admin->id));

        $this->assertSame($admin->id, (int) DB::table('seller_settlements')->value('admin_id'));
        $this->assertSame($admin->id, (int) DB::table('orders')->value('dispatched_by'));

        // El 2FA sigue siendo el mismo: no hay que volver a inscribirlo.
        $this->assertFalse(Schema::hasColumn('admin_two_factors', 'user_id'));
        $this->assertSame($secret, DB::table('admin_two_factors')->where('admin_id', $admin->id)->value('secret'));
        $this->assertTrue($moved->twoFactor->isConfirmed());

        // Auditoría: lo del admin pasa a admin_id; lo del vendedor se queda.
        $this->assertSame($admin->id, (int) DB::table('admin_audit_logs')->where('action', 'shop.approved')->value('admin_id'));
        $this->assertNull(DB::table('admin_audit_logs')->where('action', 'shop.approved')->value('user_id'));
        $this->assertSame($seller->id, (int) DB::table('admin_audit_logs')->where('action', 'products.csv_imported')->value('user_id'));

        $this->assertSame(1, $moved->notifications()->count());
    }

    public function test_down_restores_the_previous_state(): void
    {
        [$admin, , $secret] = $this->seedLegacyAdmin();

        $this->migration()->up();
        $this->migration()->down();

        $this->assertFalse(Schema::hasTable('admins'));
        $this->assertFalse(Schema::hasColumn('admin_audit_logs', 'admin_id'));
        $this->assertSame($secret, DB::table('admin_two_factors')->where('user_id', $admin->id)->value('secret'));
        $this->assertSame($admin->id, (int) DB::table('admin_audit_logs')->where('action', 'shop.approved')->value('user_id'));
        $this->assertSame($admin->id, (int) DB::table('seller_settlements')->value('admin_id'));
        $this->assertSame(1, DB::table('notifications')->where('notifiable_type', User::class)->where('notifiable_id', $admin->id)->count());

        // Y se puede volver a migrar.
        $this->migration()->up();
        $this->assertNotNull(Admin::find($admin->id));
    }

    public function test_up_refuses_to_repoint_rows_that_belong_to_a_non_admin(): void
    {
        $this->migration()->down();
        $customer = User::factory()->create(['user_type' => 'customer']);
        DB::table('orders')->insert(['code' => 'ORD-2', 'dispatched_by' => $customer->id]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('orders.dispatched_by');

        $this->migration()->up();
    }
}
