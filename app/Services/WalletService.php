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

    /** @param  array{amount: numeric, network: string, transaction_id: string}  $data */
    public function requestRecharge(User $user, array $data, ?UploadedFile $proof = null): WalletRecharge
    {
        // Un PDF pasa sin tocar; una foto del comprobante se reduce.
        $proofPath = $proof ? $this->images->store($proof, 'wallet/proofs', 'payment_proof') : null;

        return WalletRecharge::create([
            'user_id' => $user->id,
            'amount' => $data['amount'],
            'network' => $data['network'],
            'transaction_id' => $data['transaction_id'],
            'payment_proof' => $proofPath,
            'approval' => 0,
        ]);
    }

    /**
     * Solicita un retiro y descuenta el saldo en el acto (se devuelve si se
     * rechaza). Si el monto cabe en lo que viene de liquidaciones, el admin
     * ya lo aprobó al liquidar: sale aprobado sin segunda revisión. Lo que
     * exceda (saldo recargado) queda pendiente.
     *
     * @param  array{amount: numeric, full_name: string, bank_name: string, account_number: string}  $data
     *
     * @throws ValidationException si el saldo no alcanza.
     */
    public function requestWithdrawal(User $user, array $data): WalletWithdrawal
    {
        return DB::transaction(function () use ($user, $data) {
            $balance = (float) User::whereKey($user->id)->lockForUpdate()->value('balance');

            if ((float) $data['amount'] > $balance) {
                throw ValidationException::withMessages(['amount' => __('Saldo insuficiente.')]);
            }

            $fromSettlement = (float) $data['amount'] <= $this->settlements->withdrawableFor($user);

            $withdrawal = WalletWithdrawal::create([
                'user_id' => $user->id,
                'amount' => $data['amount'],
                'full_name' => $data['full_name'],
                'bank_name' => $data['bank_name'],
                'account_number' => $data['account_number'],
                'status' => $fromSettlement ? 'approved' : 'pending',
                'from_settlement' => $fromSettlement,
            ]);

            User::whereKey($user->id)->decrement('balance', $data['amount']);

            return $withdrawal;
        });
    }

    /* ─────────────────────────────────────────
     | Revisión (panel)
    ───────────────────────────────────────── */

    /** Aprueba la recarga y suma su importe al saldo del usuario. */
    public function approveRecharge(WalletRecharge $recharge, Admin $admin): WalletRecharge
    {
        $recharge = DB::transaction(function () use ($recharge) {
            $locked = WalletRecharge::whereKey($recharge->id)->lockForUpdate()->firstOrFail();

            if ((int) $locked->approval === 1) {
                throw new BusinessRuleException(__('Esta recarga ya fue aprobada.'));
            }

            $locked->update(['approval' => 1]);
            User::whereKey($locked->user_id)->increment('balance', $locked->amount);

            return $locked;
        });

        $this->audit->record('wallet.recharge.approved', $recharge, ['user_id' => $recharge->user_id, 'amount' => (float) $recharge->amount], $admin);

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

        $this->audit->record('wallet.recharge.rejected', $recharge, ['user_id' => $recharge->user_id, 'amount' => (float) $recharge->amount], $admin);

        return $recharge;
    }

    /** Solo cambia el estado: el saldo ya se descontó al solicitarlo. */
    public function approveWithdrawal(WalletWithdrawal $withdrawal, Admin $admin): WalletWithdrawal
    {
        $withdrawal = DB::transaction(function () use ($withdrawal) {
            $locked = $this->lockPendingWithdrawal($withdrawal);
            $locked->update(['status' => 'approved']);

            return $locked;
        });

        $this->audit->record('wallet.withdrawal.approved', $withdrawal, ['user_id' => $withdrawal->user_id, 'amount' => (float) $withdrawal->amount], $admin);

        return $withdrawal;
    }

    /** Rechaza el retiro y devuelve el importe al saldo del usuario. */
    public function rejectWithdrawal(WalletWithdrawal $withdrawal, Admin $admin): WalletWithdrawal
    {
        $withdrawal = DB::transaction(function () use ($withdrawal) {
            $locked = $this->lockPendingWithdrawal($withdrawal);
            $locked->update(['status' => 'rejected']);
            User::whereKey($locked->user_id)->increment('balance', $locked->amount);

            return $locked;
        });

        $this->audit->record('wallet.withdrawal.rejected', $withdrawal, ['user_id' => $withdrawal->user_id, 'amount' => (float) $withdrawal->amount], $admin);

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
