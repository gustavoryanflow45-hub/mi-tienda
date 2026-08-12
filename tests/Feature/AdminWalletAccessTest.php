<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WalletRecharge;
use App\Models\WalletWithdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

class AdminWalletAccessTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        return User::factory()->create(['user_type' => 'admin']);
    }

    /** Recarga pendiente creada por el propio usuario. */
    protected function makeRecharge(User $user): WalletRecharge
    {
        return WalletRecharge::create([
            'user_id' => $user->id,
            'amount' => 500,
            'network' => 'TRC20',
            'transaction_id' => 'tx-'.uniqid(),
            'approval' => 0,
        ]);
    }

    protected function makeWithdrawal(User $user): WalletWithdrawal
    {
        return WalletWithdrawal::create([
            'user_id' => $user->id,
            'amount' => 50,
            'full_name' => 'Titular Cuenta',
            'bank_name' => 'Banco Prueba',
            'account_number' => '1234567890',
            'status' => 'pending',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/wallet')->assertRedirect('/login');
    }

    public function test_customer_cannot_open_admin_wallet(): void
    {
        $this->actingAs($this->makeCustomer())
            ->get('/admin/wallet')
            ->assertForbidden();
    }

    public function test_seller_cannot_open_admin_wallet(): void
    {
        $this->actingAs($this->makeSeller())
            ->get('/admin/wallet')
            ->assertForbidden();
    }

    public function test_admin_can_open_admin_wallet(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/admin/wallet')
            ->assertOk();
    }

    /** El riesgo real: aprobarse la propia recarga se acredita saldo. */
    public function test_customer_cannot_approve_his_own_recharge(): void
    {
        $customer = $this->makeCustomer();
        $recharge = $this->makeRecharge($customer);
        $balanceBefore = (float) ($customer->balance ?? 0);

        $this->actingAs($customer)
            ->post("/admin/wallet/approve/{$recharge->id}")
            ->assertForbidden();

        $this->assertSame(0, (int) $recharge->fresh()->approval);
        $this->assertEquals($balanceBefore, (float) $customer->fresh()->balance);
    }

    public function test_seller_cannot_reject_a_recharge(): void
    {
        $seller = $this->makeSeller();
        $recharge = $this->makeRecharge($this->makeCustomer());

        $this->actingAs($seller)
            ->post("/admin/wallet/reject/{$recharge->id}")
            ->assertForbidden();

        $this->assertSame(0, (int) $recharge->fresh()->approval);
    }

    public function test_customer_cannot_approve_or_reject_withdrawals(): void
    {
        $customer = $this->makeCustomer();
        $withdrawal = $this->makeWithdrawal($customer);

        $this->actingAs($customer)
            ->post("/admin/wallet/withdrawal/approve/{$withdrawal->id}")
            ->assertForbidden();

        $this->actingAs($customer)
            ->post("/admin/wallet/withdrawal/reject/{$withdrawal->id}")
            ->assertForbidden();

        $this->assertSame('pending', $withdrawal->fresh()->status);
    }

    public function test_admin_can_approve_a_recharge(): void
    {
        $customer = $this->makeCustomer();
        $recharge = $this->makeRecharge($customer);

        $this->actingAs($this->makeAdmin())
            ->post("/admin/wallet/approve/{$recharge->id}")
            ->assertRedirect();

        $this->assertSame(1, (int) $recharge->fresh()->approval);
        $this->assertEquals(500.0, (float) $customer->fresh()->balance);
    }
}
