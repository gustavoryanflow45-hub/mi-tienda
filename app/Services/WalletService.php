<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Admin;
use App\Models\User;
use App\Models\WalletRecharge;
use App\Models\WalletWithdrawal;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Billetera: recargas y retiros del usuario y su revisión por el panel.
 *
 * La usan WalletController (tienda), Admin\WalletController (panel) y, más
 * adelante, la API. Cada cambio de estado bloquea la fila y vuelve a mirar
 * su estado dentro de la transacción: dos clics (o dos admins) a la vez no
 * pueden acreditar ni devolver el mismo saldo dos veces.
 */
class WalletService
{
    public function __construct(
        private SettlementService $settlements,
        private ImageOptimizer $images,
        private AuditLog $audit,
    ) {}

    /* ─────────────────────────────────────────
     | Lado del usuario
    ───────────────────────────────────────── */

    /**
     * Solicita una recarga; queda pendiente hasta que el panel la apruebe.
     * $fee es la comisión de red (la cobra la app: wallet.app_network_fee):
     * al aprobarse se acredita amount − fee.
     *
     * @param  array{amount: numeric, network: string, transaction_id: string}  $data
     *
     * @throws ValidationException si el monto no supera la comisión.
     */
    public function requestRecharge(User $user, array $data, ?UploadedFile $proof = null, float $fee = 0): WalletRecharge
    {
        $this->assertCoversFee((float) $data['amount'], $fee);

        // Un PDF pasa sin tocar; una foto del comprobante se reduce.
        $proofPath = $proof ? $this->images->store($proof, 'wallet/proofs', 'payment_proof') : null;

        return WalletRecharge::create([
            'user_id' => $user->id,
            'amount' => $data['amount'],
            'fee' => $fee,
            'network' => $data['network'],
            'transaction_id' => $data['transaction_id'],
            'payment_proof' => $proofPath,
            'approval' => 0,
        ]);
    }

    /**
     * Solicita un retiro: descuenta amount del saldo en el acto, con la fila
     * del usuario bloqueada (dos solicitudes a la vez no pueden gastar el
     * mismo saldo), y queda SIEMPRE pendiente hasta que el panel lo apruebe;
     * si lo rechaza, amount vuelve al saldo. Al aprobarlo el admin transfiere
     * amount − fee ($fee: comisión de red de la app, wallet.app_network_fee).
     *
     * from_settlement solo informa al admin de que el monto lo cubren ventas
     * ya liquidadas; ya no aprueba nada por sí mismo.
     *
     * @param  array{amount: numeric, full_name: string, bank_name: string, account_number: string}  $data
     *
     * @throws ValidationException si el monto es menor que wallet.min_withdrawal,
     *                             no supera la comisión o el saldo no alcanza.
     */
    public function requestWithdrawal(User $user, array $data, float $fee = 0): WalletWithdrawal
    {
        $minimum = (float) config('wallet.min_withdrawal', 1);

        if ((float) $data['amount'] < $minimum) {
            throw ValidationException::withMessages(['amount' => __('El retiro mínimo es de :amount.', [
                'amount' => number_format($minimum, 2),
            ])]);
        }

        $this->assertCoversFee((float) $data['amount'], $fee);

        return DB::transaction(function () use ($user, $data, $fee) {
            $balance = (float) User::whereKey($user->id)->lockForUpdate()->value('balance');

            if ((float) $data['amount'] > $balance) {
                throw ValidationException::withMessages(['amount' => __('Saldo insuficiente.')]);
            }

            $withdrawal = WalletWithdrawal::create([
                'user_id' => $user->id,
                'amount' => $data['amount'],
                'fee' => $fee,
                'full_name' => $data['full_name'],
                'bank_name' => $data['bank_name'],
                'account_number' => $data['account_number'],
                'status' => 'pending',
                'from_settlement' => (float) $data['amount'] <= $this->settlements->withdrawableFor($user),
            ]);

            User::whereKey($user->id)->decrement('balance', $data['amount']);

            return $withdrawal;
        });
    }

    /** Una solicitud con comisión tiene que dejar algo después de cobrarla. */
    protected function assertCoversFee(float $amount, float $fee): void
    {
        if ($fee > 0 && $amount <= $fee) {
            throw ValidationException::withMessages(['amount' => __('El monto debe ser mayor que la comisión de red (:fee).', [
                'fee' => number_format($fee, 2),
            ])]);
        }
    }

    /* ─────────────────────────────────────────
     | Revisión (panel)
    ───────────────────────────────────────── */

    /** Aprueba la recarga y suma su importe, menos su comisión, al saldo del usuario. */
    public function approveRecharge(WalletRecharge $recharge, Admin $admin): WalletRecharge
    {
        $recharge = DB::transaction(function () use ($recharge) {
            $locked = WalletRecharge::whereKey($recharge->id)->lockForUpdate()->firstOrFail();

            if ((int) $locked->approval === 1) {
                throw new BusinessRuleException(__('Esta recarga ya fue aprobada.'));
            }

            $locked->update(['approval' => 1]);
            User::whereKey($locked->user_id)->increment('balance', $locked->netAmount());

            return $locked;
        });

        $this->audit->record('wallet.recharge.approved', $recharge, [
            'user_id' => $recharge->user_id, 'amount' => (float) $recharge->amount, 'fee' => (float) $recharge->fee,
        ], $admin);

        return $recharge;
    }

    public function rejectRecharge(WalletRecharge $recharge, Admin $admin): WalletRecharge
    {
        $recharge = DB::transaction(function () use ($recharge) {
            $locked = WalletRecharge::whereKey($recharge->id)->lockForUpdate()->firstOrFail();

            if ((int) $locked->approval === 1) {
                throw new BusinessRuleException(__('No se puede rechazar una recarga ya aprobada.'));
            }

            $locked->update(['approval' => -1]);

            return $locked;
        });

        $this->audit->record('wallet.recharge.rejected', $recharge, [
            'user_id' => $recharge->user_id, 'amount' => (float) $recharge->amount, 'fee' => (float) $recharge->fee,
        ], $admin);

        return $recharge;
    }

    /**
     * Solo cambia el estado: el saldo ya se descontó al solicitarlo. El admin
     * transfiere aparte (fuera del sistema) amount − fee.
     */
    public function approveWithdrawal(WalletWithdrawal $withdrawal, Admin $admin): WalletWithdrawal
    {
        $withdrawal = DB::transaction(function () use ($withdrawal) {
            $locked = $this->lockPendingWithdrawal($withdrawal);
            $locked->update(['status' => 'approved']);

            return $locked;
        });

        $this->audit->record('wallet.withdrawal.approved', $withdrawal, [
            'user_id' => $withdrawal->user_id, 'amount' => (float) $withdrawal->amount, 'fee' => (float) $withdrawal->fee,
        ], $admin);

        return $withdrawal;
    }

    /** Rechaza el retiro y devuelve el importe completo (sin comisión: no hubo transferencia). */
    public function rejectWithdrawal(WalletWithdrawal $withdrawal, Admin $admin): WalletWithdrawal
    {
        $withdrawal = DB::transaction(function () use ($withdrawal) {
            $locked = $this->lockPendingWithdrawal($withdrawal);
            $locked->update(['status' => 'rejected']);
            User::whereKey($locked->user_id)->increment('balance', $locked->amount);

            return $locked;
        });

        $this->audit->record('wallet.withdrawal.rejected', $withdrawal, [
            'user_id' => $withdrawal->user_id, 'amount' => (float) $withdrawal->amount, 'fee' => (float) $withdrawal->fee,
        ], $admin);

        return $withdrawal;
    }

    protected function lockPendingWithdrawal(WalletWithdrawal $withdrawal): WalletWithdrawal
    {
        $locked = WalletWithdrawal::whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();

        if ($locked->status !== 'pending') {
            throw new BusinessRuleException(__('Este retiro ya fue procesado.'));
        }

        return $locked;
    }
}
