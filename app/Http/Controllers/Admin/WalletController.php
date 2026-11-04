<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletRecharge;
use App\Models\WalletWithdrawal;
use App\Services\AuditLog;
use Illuminate\Support\Facades\DB;

/**
 * Revisión de recargas y retiros (/admin/wallet). Lo que hace el propio
 * usuario —recargar, retirar, ver su historial— está en
 * App\Http\Controllers\WalletController.
 */
class WalletController extends Controller
{
    public function __construct()
    {
        // Las rutas ya llevan 'auth:admin' + 'admin'; defensa en profundidad como
        // en Admin\ShopController.
        $this->middleware(['auth:admin', 'admin']);
    }

    // ── GET /admin/wallet ────────────────────────────────────────
    public function index()
    {
        $recharges = WalletRecharge::with('user')->latest()->get();
        $withdrawals = WalletWithdrawal::with('user')->latest()->get();

        return view('admin.wallet', compact('recharges', 'withdrawals'));
    }

    /* ─────────────────────────────────────────
     | RECARGAS
    ───────────────────────────────────────── */

    public function approve($id, AuditLog $audit)
    {
        $recharge = WalletRecharge::findOrFail($id);

        if ($recharge->approval == 1) {
            return back()->with('warning', __('Esta recarga ya fue aprobada.'));
        }

        DB::transaction(function () use ($recharge) {
            $recharge->update(['approval' => 1]);
            User::where('id', $recharge->user_id)
                ->increment('balance', $recharge->amount);
        });

        $audit->record('wallet.recharge.approved', $recharge, ['user_id' => $recharge->user_id, 'amount' => (float) $recharge->amount]);

        return back()->with('success', __('Recarga #:id aprobada. Se sumaron $:amount al saldo del usuario.', ['id' => $recharge->id, 'amount' => $recharge->amount]));
    }

    public function reject($id, AuditLog $audit)
    {
        $recharge = WalletRecharge::findOrFail($id);

        if ($recharge->approval == 1) {
            return back()->with('warning', __('No se puede rechazar una recarga ya aprobada.'));
        }

        $recharge->update(['approval' => -1]);
        $audit->record('wallet.recharge.rejected', $recharge, ['user_id' => $recharge->user_id, 'amount' => (float) $recharge->amount]);

        return back()->with('error', __('Recarga #:id rechazada.', ['id' => $recharge->id]));
    }

    /* ─────────────────────────────────────────
     | RETIROS
    ───────────────────────────────────────── */

    public function approveWithdrawal($id, AuditLog $audit)
    {
        $withdrawal = WalletWithdrawal::findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return back()->with('warning', __('Este retiro ya fue procesado.'));
        }

        // Solo marcar como aprobado — el saldo ya se descontó al crear la solicitud
        $withdrawal->update(['status' => 'approved']);
        $audit->record('wallet.withdrawal.approved', $withdrawal, ['user_id' => $withdrawal->user_id, 'amount' => (float) $withdrawal->amount]);

        return back()->with('success', __('Retiro #:id aprobado por $:amount.', ['id' => $withdrawal->id, 'amount' => $withdrawal->amount]));
    }

    public function rejectWithdrawal($id, AuditLog $audit)
    {
        $withdrawal = WalletWithdrawal::findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return back()->with('warning', __('Este retiro ya fue procesado.'));
        }

        DB::transaction(function () use ($withdrawal) {
            $withdrawal->update(['status' => 'rejected']);

            // Devolver el saldo al usuario
            User::where('id', $withdrawal->user_id)
                ->increment('balance', $withdrawal->amount);
        });

        $audit->record('wallet.withdrawal.rejected', $withdrawal, ['user_id' => $withdrawal->user_id, 'amount' => (float) $withdrawal->amount]);

        return back()->with('success', __('Retiro #:id rechazado. Saldo devuelto al usuario.', ['id' => $withdrawal->id]));
    }
}
