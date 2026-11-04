<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Las cuentas del panel solo nacen y cambian de contraseña desde la consola,
 * con la política de contraseñas del panel (Admin::passwordRule()).
 */
class AdminAccountCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected const STRONG = 'Bodega#Norte2026';

    public function test_admin_create_makes_a_panel_account_and_not_a_user(): void
    {
        $this->artisan('admin:create', ['email' => 'Ops@Tienda.test', '--name' => 'Operaciones'])
            ->expectsQuestion('Contraseña (mín. 12, mayúsculas, minúsculas, números y símbolos)', self::STRONG)
            ->expectsQuestion('Repite la contraseña', self::STRONG)
            ->expectsOutputToContain('admin:2fa-invite ops@tienda.test')
            ->assertSuccessful();

        $admin = Admin::where('email', 'ops@tienda.test')->firstOrFail();
        $this->assertSame('Operaciones', $admin->name);
        $this->assertTrue(Hash::check(self::STRONG, $admin->password));
        $this->assertFalse(User::where('email', 'ops@tienda.test')->exists());
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'admin.created', 'subject_id' => $admin->id]);
    }

    public function test_admin_create_rejects_a_weak_password(): void
    {
        $this->artisan('admin:create', ['email' => 'ops@tienda.test'])
            ->expectsQuestion('Contraseña (mín. 12, mayúsculas, minúsculas, números y símbolos)', 'secreto123')
            ->assertFailed();

        $this->assertSame(0, Admin::count());
    }

    public function test_admin_create_rejects_a_mismatched_confirmation_and_a_duplicate(): void
    {
        $this->artisan('admin:create', ['email' => 'ops@tienda.test'])
            ->expectsQuestion('Contraseña (mín. 12, mayúsculas, minúsculas, números y símbolos)', self::STRONG)
            ->expectsQuestion('Repite la contraseña', self::STRONG.'x')
            ->expectsOutput('Las contraseñas no coinciden.')
            ->assertFailed();

        Admin::factory()->create(['email' => 'ops@tienda.test']);

        $this->artisan('admin:create', ['email' => 'ops@tienda.test'])->assertFailed();
        $this->assertSame(1, Admin::count());
    }

    public function test_admin_password_changes_the_password_with_the_same_policy(): void
    {
        $admin = Admin::factory()->create();

        $this->artisan('admin:password', ['email' => $admin->email])
            ->expectsQuestion('Contraseña (mín. 12, mayúsculas, minúsculas, números y símbolos)', 'corta')
            ->assertFailed();

        $this->artisan('admin:password', ['email' => $admin->email])
            ->expectsQuestion('Contraseña (mín. 12, mayúsculas, minúsculas, números y símbolos)', self::STRONG)
            ->expectsQuestion('Repite la contraseña', self::STRONG)
            ->assertSuccessful();

        $this->assertTrue(Hash::check(self::STRONG, $admin->fresh()->password));
    }

    public function test_admin_password_does_not_touch_storefront_accounts(): void
    {
        $user = User::factory()->create(['user_type' => 'admin']);

        $this->artisan('admin:password', ['email' => $user->email])
            ->expectsOutput('No hay ningún admin con ese correo.')
            ->assertFailed();
    }
}
