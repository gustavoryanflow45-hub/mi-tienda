<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use App\Models\WalletRecharge;
use App\Models\WalletWithdrawal;
use App\Services\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\Concerns\BuildsCheckoutData;
use Tests\Concerns\PassesAdminTwoFactor;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use BuildsCheckoutData;
    use PassesAdminTwoFactor;
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        return User::factory()->create(['user_type' => 'admin']);
    }

    protected function makeDeliveredOrder(User $customer, User $seller): Order
    {
        $order = $this->makePendingOrder($customer, $seller, 200.00);
        $order->markPaid('stripe', 'pi_audit_'.uniqid());
        $order->update(['delivery_status' => 'delivered', 'delivered_at' => now()]);
        $order->orderDetails()->update(['delivery_status' => 'delivered']);

        return $order->fresh();
    }

    protected function lastLog(string $action): ?AdminAuditLog
    {
        return AdminAuditLog::where('action', $action)->latest('id')->first();
    }

    // ── Acciones del admin ───────────────────────────────────────

    public function test_shop_decisions_are_logged_once(): void
    {
        $admin = $this->makeAdmin();
        $seller = $this->makePendingSeller();

        $this->actingAsAdmin($admin)
            ->withHeader('User-Agent', 'AuditBrowser/1.0')
            ->post("/admin/shops/{$seller->shop->id}/approve");

        $log = $this->lastLog('shop.approved');
        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame((new Shop)->getMorphClass(), $log->subject_type);
        $this->assertSame($seller->shop->id, (int) $log->subject_id);
        $this->assertSame(['seller_id' => $seller->id], $log->meta);
        $this->assertSame('127.0.0.1', $log->ip);
        $this->assertSame('AuditBrowser/1.0', $log->user_agent);
        $this->assertNotNull($log->created_at);

        // Una re-aprobación sin cambios no deja rastro nuevo.
        $this->actingAsAdmin($admin)->post("/admin/shops/{$seller->shop->id}/approve");
        $this->assertSame(1, AdminAuditLog::where('action', 'shop.approved')->count());

        $this->actingAsAdmin($admin)->post("/admin/shops/{$seller->shop->id}/reject");
        $this->assertNotNull($this->lastLog('shop.rejected'));
    }

    public function test_settlement_is_logged_with_its_amounts(): void
    {
        $admin = $this->makeAdmin();
        $seller = $this->makeSeller();
        $this->makeDeliveredOrder($this->makeCustomer(), $seller);

        $this->actingAsAdmin($admin)->post('/admin/settlements/'.$seller->id)->assertSessionHas('success');

        $log = $this->lastLog('seller.settled');
        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($seller->id, $log->meta['seller_id']);
        $this->assertEquals(200.0, $log->meta['total_sales']);
        $this->assertEqualsCanonicalizing(
            ['seller_id', 'total_sales', 'commission', 'net_amount', 'lines_count'],
            array_keys($log->meta),
        );
    }

    public function test_wallet_decisions_are_logged_without_proofs_or_bank_data(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $recharge = WalletRecharge::create([
            'user_id' => $customer->id,
            'amount' => 500,
            'payment_proof' => 'wallet/proofs/comprobante.webp',
            'approval' => 0,
        ]);
        $withdrawal = WalletWithdrawal::create([
            'user_id' => $customer->id,
            'amount' => 50,
            'full_name' => 'Titular Cuenta',
            'bank_name' => 'Banco Prueba',
            'account_number' => '1234567890',
            'status' => 'pending',
        ]);

        $this->actingAsAdmin($admin)->post("/admin/wallet/approve/{$recharge->id}");
        $this->actingAsAdmin($admin)->post("/admin/wallet/withdrawal/reject/{$withdrawal->id}");

        foreach (['wallet.recharge.approved', 'wallet.withdrawal.rejected'] as $action) {
            $log = $this->lastLog($action);
            $this->assertNotNull($log, $action);
            $this->assertSame(['user_id', 'amount'], array_keys($log->meta));
        }

        $all = AdminAuditLog::all()->toJson();
        $this->assertStringNotContainsString('comprobante', $all);
        $this->assertStringNotContainsString('1234567890', $all);
    }

    public function test_warehouse_dispatch_and_delivery_are_logged(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makePendingOrder($this->makeCustomer(), $this->makeSeller());
        $order->markPaid('stripe', 'pi_audit_wh');
        $order->update(['delivery_status' => 'warehouse', 'warehouse_at' => now()]);

        $this->actingAsAdmin($admin)->post("/admin/warehouse/orders/{$order->id}/dispatch");
        $this->actingAsAdmin($admin)->post("/admin/warehouse/orders/{$order->id}/deliver");

        $this->assertSame($order->id, (int) $this->lastLog('order.dispatched')?->subject_id);
        $this->assertSame($order->id, (int) $this->lastLog('order.delivered')?->subject_id);
    }

    // ── Acciones de vendedor ─────────────────────────────────────

    public function test_csv_import_is_logged_with_counts_only(): void
    {
        $seller = $this->makeSeller();
        $seller->forceFill(['email_verified_at' => now()])->save();
        $category = Category::firstOrCreate(['slug' => 'general'], ['name' => 'General']);

        $csv = "name,category_id,unit_price,stock_qty,stock_price\n"
            ."Producto secreto,{$category->id},10,5,10\n"
            .",,,,\n"
            ."Sin categoria,,10,5,10\n";

        $this->actingAs($seller)->post('/seller/products/bulk', [
            'csv_file' => UploadedFile::fake()->createWithContent('productos.csv', $csv),
        ])->assertRedirect(route('seller.products.index'));

        $log = $this->lastLog('products.csv_imported');
        $this->assertNotNull($log);
        $this->assertSame($seller->id, $log->user_id);
        $this->assertSame(['created' => 1, 'errors' => 1], $log->meta);
        $this->assertStringNotContainsString('Producto secreto', $log->toJson());
    }

    // ── Acceso al panel ──────────────────────────────────────────

    public function test_admin_login_attempts_and_failed_codes_are_logged(): void
    {
        $admin = $this->makeAdmin();
        $admin->forceFill(['password' => Hash::make('secreto123')])->save();

        $this->post('/admin/login', ['email' => strtoupper($admin->email), 'password' => 'mala']);
        $failed = $this->lastLog('admin.login_failed');
        $this->assertNull($failed->user_id);
        $this->assertSame(['email' => strtolower($admin->email)], $failed->meta);

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'secreto123']);
        $this->assertSame($admin->id, $this->lastLog('admin.login')?->user_id);
        $this->assertStringNotContainsString('secreto123', AdminAuditLog::all()->toJson());
    }

    // ── El registro en sí ────────────────────────────────────────

    public function test_sensitive_meta_keys_are_dropped(): void
    {
        $log = app(AuditLog::class)->record('test.action', null, [
            'amount' => 10,
            'card_number' => '4242424242424242',
            'payment_proof' => 'x.webp',
            'password' => 'x',
            'recovery_code' => 'ABCDE-FGHIJ',
            'nested' => ['account_number' => '1', 'ok' => true],
        ]);

        $this->assertSame(['amount' => 10, 'nested' => ['ok' => true]], $log->fresh()->meta);
    }

    public function test_entries_cannot_be_changed_or_deleted(): void
    {
        $log = app(AuditLog::class)->record('test.action');

        try {
            $log->update(['action' => 'otra']);
            $this->fail('Se pudo modificar una entrada de auditoría.');
        } catch (LogicException) {
        }

        try {
            $log->delete();
            $this->fail('Se pudo borrar una entrada de auditoría.');
        } catch (LogicException) {
        }

        $this->assertSame('test.action', $log->fresh()->action);
    }

    public function test_the_new_migrations_are_reversible(): void
    {
        foreach ([
            '2026_10_27_130000_create_admin_audit_logs_table.php' => 'admin_audit_logs',
            '2026_10_27_120000_create_admin_two_factors_table.php' => 'admin_two_factors',
        ] as $file => $table) {
            $migration = require database_path('migrations/'.$file);

            $migration->down();
            $this->assertFalse(Schema::hasTable($table), "{$file}: down() no borró {$table}");

            $migration->up();
            $this->assertTrue(Schema::hasTable($table), "{$file}: up() no recreó {$table}");
        }
    }
}
