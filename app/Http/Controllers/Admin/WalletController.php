<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Models\WalletRecharge;
use App\Models\WalletWithdrawal;
use App\Services\WalletService;
use Illuminate\Http\Request;

/**
 * Revisión de recargas y retiros (/admin/wallet). La lógica está en
 * WalletService; lo que hace el propio usuario —recargar, retirar, ver su
 * historial— está en App\Http\Controllers\WalletController.
 */
class WalletController extends Controller
{
    public function __construct(private WalletService $wallet)
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

    public function approve(Request $request, $id)
    {
        try {
            $recharge = $this->wallet->approveRecharge(WalletRecharge::findOrFail($id), $request->user('admin'));
        } catch (BusinessRuleException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return back()->with('success', __('Recarga #:id aprobada. Se sumaron $:amount al saldo del usuario.', ['id' => $recharge->id, 'amount' => $recharge->amount]));
    }

    public function reject(Request $request, $id)
    {
        try {
            $recharge = $this->wallet->rejectRecharge(WalletRecharge::findOrFail($id), $request->user('admin'));
        } catch (BusinessRuleException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return back()->with('error', __('Recarga #:id rechazada.', ['id' => $recharge->id]));
    }

    /* ─────────────────────────────────────────
     | RETIROS
    ───────────────────────────────────────── */

    public function approveWithdrawal(Request $request, $id)
    {
        try {
            $withdrawal = $this->wallet->approveWithdrawal(WalletWithdrawal::findOrFail($id), $request->user('admin'));
        } catch (BusinessRuleException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return back()->with('success', __('Retiro #:id aprobado por $:amount.', ['id' => $withdrawal->id, 'amount' => $withdrawal->amount]));
    }

    public function rejectWithdrawal(Request $request, $id)
    {
        try {
            $withdrawal = $this->wallet->rejectWithdrawal(WalletWithdrawal::findOrFail($id), $request->user('admin'));
        } catch (BusinessRuleException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return back()->with('success', __('Retiro #:id rechazado. Saldo devuelto al usuario.', ['id' => $withdrawal->id]));
    }
}
