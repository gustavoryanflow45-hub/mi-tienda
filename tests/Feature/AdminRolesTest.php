<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Models\Order;
use App\Models\User;
use App\Models\WalletRecharge;
use App\Support\AdminPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsCheckoutData;
use Tests\Concerns\PassesAdminTwoFactor;
use Tests\TestCase;

/**
 * Roles del panel (spatie, guard 'admin'): super-admin, finanzas,
 * operaciones y soporte (solo lectura). Cada ruta del panel exige su
 * permiso y la interfaz esconde lo que el rol no puede usar.
 */
class AdminRolesTest extends TestCase
{
    use BuildsCheckoutData;
    use PassesAdminTwoFactor;
    use RefreshDatabase;

    protected function as(string $role): static
    {
        return $this->actingAsAdmin(Admin::factory()->role($role)->create());
    }

    protected function paidOrder(): Order
    {
        $order = $this->makePendingOrder($this->makeCustomer(), $this->makeSeller());
        $order->markPaid('stripe', 'pi_roles');

        return $order->fresh();
    }

    /** Secciones (GET) que cada rol ve. */
    public static function sections(): array
    {
        return [
            'super-admin' => ['super-admin', ['shops', 'products', 'orders', 'settlements', 'wallet', 'warehouse']],
            'finanzas' => ['finanzas', ['shops', 'products', 'orders', 'settlements', 'wallet']],
            'operaciones' => ['operaciones', ['shops', 'products', 'orders', 'warehouse']],
            'soporte' => ['soporte', ['shops', 'products', 'orders', 'settlements', 'wallet', 'warehouse']],
        ];
    }

    /** @dataProvider sections */
    public function test_each_role_sees_only_its_sections(string $role, array $visible): void
    {
        foreach (['shops', 'products', 'orders', 'settlements', 'wallet', 'warehouse'] as $section) {
            $response = $this->as($role)->get("/admin/{$section}");

            in_array($section, $visible, true)
                ? $response->assertOk()
                : $response->assertForbidden();
        }
    }

    public function test_support_is_read_only(): void
    {
        $seller = $this->makePendingSeller();
        $product = $this->makeProduct($this->makeSeller());
        $order = $this->paidOrder();
        $order->update(['delivery_status' => 'warehouse']);
        $recharge = WalletRecharge::create(['user_id' => $this->makeCustomer()->id, 'amount' => 10, 'network' => 'TRC20', 'transaction_id' => 't', 'approval' => 0]);

        $this->as('soporte')->post("/admin/shops/{$seller->shop->id}/approve")->assertForbidden();
        $this->as('soporte')->post("/admin/products/{$product->id}/disable")->assertForbidden();
        $this->as('soporte')->get("/admin/products/{$product->id}/edit")->assertForbidden();
        $this->as('soporte')->post("/admin/warehouse/orders/{$order->id}/dispatch")->assertForbidden();
        $this->as('soporte')->post("/admin/wallet/approve/{$recharge->id}")->assertForbidden();
        $this->as('soporte')->post("/admin/settlements/{$seller->id}")->assertForbidden();

        $this->assertSame(0, (int) $seller->shop->fresh()->status);
        $this->assertSame(1, (int) $product->fresh()->approved);
        $this->assertSame('warehouse', $order->fresh()->delivery_status);
        $this->assertSame(0, (int) $recharge->fresh()->approval);

        // Y no se le ofrecen los botones.
        $this->as('soporte')->get('/admin/shops')->assertOk()
            ->assertDontSee(route('admin.shops.approve', $seller->shop->id), false)
            ->assertSee(__('Solo lectura'));
        $this->as('soporte')->get('/admin/warehouse')->assertOk()
            ->assertDontSee(route('admin.warehouse.dispatch', $order->id), false);
        $this->as('soporte')->get("/admin/products/{$product->id}")->assertOk()
            ->assertDontSee(route('admin.products.disable', $product->id), false)
            ->assertDontSee(route('admin.products.edit', $product->id), false);
    }

    public function test_finance_moves_money_but_not_the_catalog_or_the_warehouse(): void
    {
        $customer = $this->makeCustomer();
        $recharge = WalletRecharge::create(['user_id' => $customer->id, 'amount' => 10, 'network' => 'TRC20', 'transaction_id' => 't', 'approval' => 0]);
        $seller = $this->makePendingSeller();
        $product = $this->makeProduct($this->makeSeller());

        $this->as('finanzas')->post("/admin/wallet/approve/{$recharge->id}")->assertSessionHas('success');
        $this->assertEquals(10.0, (float) $customer->fresh()->balance);

        $this->as('finanzas')->post("/admin/shops/{$seller->shop->id}/approve")->assertForbidden();
        $this->as('finanzas')->post("/admin/products/{$product->id}/disable")->assertForbidden();
    }

    public function test_operations_runs_catalog_and_warehouse_but_not_money(): void
    {
        $seller = $this->makePendingSeller();
        $order = $this->paidOrder();
        $order->update(['delivery_status' => 'warehouse']);
        $recharge = WalletRecharge::create(['user_id' => $this->makeCustomer()->id, 'amount' => 10, 'network' => 'TRC20', 'transaction_id' => 't', 'approval' => 0]);

        $this->as('operaciones')->post("/admin/shops/{$seller->shop->id}/approve")->assertSessionHas('success');
        $this->as('operaciones')->post("/admin/warehouse/orders/{$order->id}/dispatch")->assertSessionHas('warehouse_success');

        $this->as('operaciones')->post("/admin/wallet/approve/{$recharge->id}")->assertForbidden();
        $this->as('operaciones')->post("/admin/settlements/{$seller->id}")->assertForbidden();
    }

    public function test_the_nav_only_lists_the_sections_of_the_role(): void
    {
        $this->as('operaciones')->get('/admin/shops')->assertOk()
            ->assertSee(route('admin.warehouse.index'), false)
            ->assertDontSee(route('admin.wallet'), false)
            ->assertDontSee(route('admin.settlements'), false);
    }

    public function test_home_goes_to_the_first_allowed_section_and_a_roleless_account_is_refused(): void
    {
        $this->as('operaciones')->get('/admin')->assertRedirect(route('admin.shops'));

        $roleless = Admin::factory()->create();
        $roleless->syncRoles([]);

        $this->actingAsAdmin($roleless)->get('/admin')->assertForbidden();
        $this->actingAsAdmin($roleless)->get('/admin/shops')->assertForbidden();
    }

    // ── Consola ──────────────────────────────────────────────────

    public function test_admin_create_defaults_to_the_lowest_role(): void
    {
        $this->artisan('admin:create', ['email' => 'nuevo@tienda.test'])
            ->expectsQuestion('Contraseña (mín. 12, mayúsculas, minúsculas, números y símbolos)', 'Bodega#Norte2026')
            ->expectsQuestion('Repite la contraseña', 'Bodega#Norte2026')
            ->assertSuccessful();

        $this->assertSame(['soporte'], Admin::where('email', 'nuevo@tienda.test')->first()->getRoleNames()->all());

        $this->artisan('admin:create', ['email' => 'otro@tienda.test', '--role' => 'jefe'])->assertFailed();
    }

    public function test_admin_role_changes_the_role_and_keeps_one_super_admin(): void
    {
        $boss = Admin::factory()->create();
        $ops = Admin::factory()->role('soporte')->create();

        $this->artisan('admin:role', ['email' => $ops->email, 'role' => 'operaciones'])->assertSuccessful();
        $this->assertSame(['operaciones'], $ops->fresh()->getRoleNames()->all());
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'admin.role_assigned', 'subject_id' => $ops->id]);
        $this->assertSame('soporte', AdminAuditLog::where('action', 'admin.role_assigned')->first()->meta['previous']);

        // El único super-admin no puede bajarse a sí mismo.
        $this->artisan('admin:role', ['email' => $boss->email, 'role' => 'soporte'])->assertFailed();
        $this->assertTrue($boss->fresh()->hasRole('super-admin'));

        $this->artisan('admin:role', ['--list' => true])->assertSuccessful();
        $this->artisan('admin:role', ['email' => $ops->email, 'role' => 'inventado'])->assertFailed();
    }

    public function test_roles_sync_is_idempotent_and_super_admin_gets_every_permission(): void
    {
        $this->artisan('admin:roles-sync')->assertSuccessful();
        $this->artisan('admin:roles-sync')->assertSuccessful();

        $this->assertSame(4, Role::where('guard_name', 'admin')->count());
        $this->assertEqualsCanonicalizing(
            array_keys(AdminPermissions::PERMISSIONS),
            Role::findByName('super-admin', 'admin')->permissions->pluck('name')->all(),
        );
    }

    public function test_the_migration_makes_existing_admins_super_admin(): void
    {
        // Una cuenta de antes de los roles: down() le quita el suyo y borra los roles.
        $existing = Admin::factory()->create();
        $migration = require database_path('migrations/2026_11_05_100100_seed_admin_roles.php');
        $migration->down();
        $this->assertSame(0, DB::table('model_has_roles')->count());

        $migration->up();

        $this->assertTrue($existing->fresh()->hasRole('super-admin'));
    }

    // ── Restricción por IP ───────────────────────────────────────

    public function test_the_panel_only_answers_to_allowed_ips(): void
    {
        config(['admin.allowed_ips' => ['203.0.113.7', '10.0.0.0/8']]);
        $admin = Admin::factory()->create();

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])->get('/admin/login')->assertNotFound();
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])->post('/admin/login', ['email' => 'x@y.z', 'password' => 'x'])->assertNotFound();
        // Antes de iniciar sesión: el login redirige a quien ya está dentro.
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])->get('/admin/login')->assertOk();

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])->actingAsAdmin($admin)->get('/admin/shops')->assertNotFound();
        $this->withServerVariables(['REMOTE_ADDR' => '10.20.30.40'])->actingAsAdmin($admin)->get('/admin/shops')->assertOk();

        // La tienda no se ve afectada.
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])->get('/')->assertOk();
    }

    public function test_an_empty_allowlist_does_not_restrict(): void
    {
        config(['admin.allowed_ips' => []]);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])->get('/admin/login')->assertOk();
    }

    public function test_storefront_users_still_never_reach_the_panel(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => 'admin']))
            ->get('/admin/shops')
            ->assertRedirect('/admin/login');
    }
}
