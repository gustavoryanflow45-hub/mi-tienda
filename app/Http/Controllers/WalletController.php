<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\SellerSettlement;
use App\Models\WalletRecharge;
use App\Models\WalletWithdrawal;
use App\Models\User;
use App\Services\SettlementService;

class WalletController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ─────────────────────────────────────────
     | VISTA USUARIO
    ───────────────────────────────────────── */

    public function index(SettlementService $settlementService)
    {
        $recharges   = WalletRecharge::where('user_id', Auth::id())->latest()->get();
        $withdrawals = WalletWithdrawal::where('user_id', Auth::id())->latest()->get();
        // Liquidaciones de ventas acreditadas por el admin (solo vendedores las tienen).
        $settlements = SellerSettlement::where('seller_id', Auth::id())->latest('settled_at')->get();
        // Parte del saldo que se retira sin aprobación: ya la aprobó el admin al liquidar.
        $withdrawable = $settlementService->withdrawableFor(Auth::user());
        return view('users.wallet', compact('recharges', 'withdrawals', 'settlements', 'withdrawable'));
    }

    /* ─────────────────────────────────────────
     | VISTA ADMIN
    ───────────────────────────────────────── */

    public function adminIndex()
    {
        $recharges   = WalletRecharge::with('user')->latest()->get();
        $withdrawals = WalletWithdrawal::with('user')->latest()->get();
        return view('admin.wallet', compact('recharges', 'withdrawals'));
    }

    /* ─────────────────────────────────────────
     | RECARGAS
    ───────────────────────────────────────── */

    public function recharge(Request $request)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:1',
            'network'        => 'required|in:BEP20,TRC20,ERC20',
            'transaction_id' => 'required|string|max:255',
            'payment_proof'  => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        $proofPath = null;
        if ($request->hasFile('payment_proof')) {
            $proofPath = $request->file('payment_proof')
                            ->store('wallet/proofs', 'public');
        }

        WalletRecharge::create([
            'user_id'        => Auth::id(),
            'amount'         => $request->amount,
            'network'        => $request->network,
            'transaction_id' => $request->transaction_id,
            'payment_proof'  => $proofPath,
            'approval'       => 0,
        ]);

        return back()->with('success', __('Solicitud de recarga enviada. Está pendiente de aprobación.'));
    }

    public function approve($id)
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

        return back()->with('success', __('Recarga #:id aprobada. Se sumaron $:amount al saldo del usuario.', ['id' => $recharge->id, 'amount' => $recharge->amount]));
    }

    public function reject($id)
    {
        $recharge = WalletRecharge::findOrFail($id);

        if ($recharge->approval == 1) {
            return back()->with('warning', __('No se puede rechazar una recarga ya aprobada.'));
        }

        $recharge->update(['approval' => -1]);

        return back()->with('error', __('Recarga #:id rechazada.', ['id' => $recharge->id]));
    }

    /* ─────────────────────────────────────────
     | RETIROS
    ───────────────────────────────────────── */

    public function withdraw(Request $request, SettlementService $settlementService)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:1',
            'full_name'      => 'required|string|max:255',
            'bank_name'      => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
        ]);

        $balance = Auth::user()->balance ?? 0;

        if ($request->amount > $balance) {
            return back()->withErrors(['amount' => __('Saldo insuficiente.')])->withInput();
        }

        // Si el monto cabe en lo que viene de liquidaciones, el admin ya lo
        // aprobó al liquidar: sale aprobado sin segunda revisión. Lo que
        // exceda (saldo recargado) sigue pendiente como siempre.
        $fromSettlement = (float) $request->amount <= $settlementService->withdrawableFor(Auth::user());

        DB::transaction(function () use ($request, $fromSettlement) {
            WalletWithdrawal::create([
                'user_id'         => Auth::id(),
                'amount'          => $request->amount,
                'full_name'       => $request->full_name,
                'bank_name'       => $request->bank_name,
                'account_number'  => $request->account_number,
                'status'          => $fromSettlement ? 'approved' : 'pending',
                'from_settlement' => $fromSettlement,
            ]);

            // Descontar el saldo al solicitar (se devuelve si se rechaza)
            User::where('id', Auth::id())
                ->decrement('balance', $request->amount);
        });

        return back()->with('success', $fromSettlement
            ? __('Retiro aprobado: proviene de ventas ya liquidadas, no necesita otra aprobación.')
            : __('Solicitud de retiro enviada. Está pendiente de aprobación.'));
    }

    public function approveWithdrawal($id)
    {
        $withdrawal = WalletWithdrawal::findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return back()->with('warning', __('Este retiro ya fue procesado.'));
        }

        // Solo marcar como aprobado — el saldo ya se descontó al crear la solicitud
        $withdrawal->update(['status' => 'approved']);

        return back()->with('success', __('Retiro #:id aprobado por $:amount.', ['id' => $withdrawal->id, 'amount' => $withdrawal->amount]));
    }

    public function rejectWithdrawal($id)
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

        return back()->with('success', __('Retiro #:id rechazado. Saldo devuelto al usuario.', ['id' => $withdrawal->id]));
    }
}