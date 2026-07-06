<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\WalletRecharge;
use App\Models\WalletWithdrawal;
use App\Models\User;

class WalletController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ─────────────────────────────────────────
     | VISTA USUARIO
    ───────────────────────────────────────── */

    public function index()
    {
        $recharges   = WalletRecharge::where('user_id', Auth::id())->latest()->get();
        $withdrawals = WalletWithdrawal::where('user_id', Auth::id())->latest()->get();
        return view('users.wallet', compact('recharges', 'withdrawals'));
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

        return back()->with('success', 'Solicitud de recarga enviada. Está pendiente de aprobación.');
    }

    public function approve($id)
    {
        $recharge = WalletRecharge::findOrFail($id);

        if ($recharge->approval == 1) {
            return back()->with('warning', 'Esta recarga ya fue aprobada.');
        }

        DB::transaction(function () use ($recharge) {
            $recharge->update(['approval' => 1]);
            User::where('id', $recharge->user_id)
                ->increment('balance', $recharge->amount);
        });

        return back()->with('success', "Recarga #{$recharge->id} aprobada. Se sumaron \${$recharge->amount} al saldo del usuario.");
    }

    public function reject($id)
    {
        $recharge = WalletRecharge::findOrFail($id);

        if ($recharge->approval == 1) {
            return back()->with('warning', 'No se puede rechazar una recarga ya aprobada.');
        }

        $recharge->update(['approval' => -1]);

        return back()->with('error', "Recarga #{$recharge->id} rechazada.");
    }

    /* ─────────────────────────────────────────
     | RETIROS
    ───────────────────────────────────────── */

    public function withdraw(Request $request)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:1',
            'full_name'      => 'required|string|max:255',
            'bank_name'      => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
        ]);

        $balance = Auth::user()->balance ?? 0;

        if ($request->amount > $balance) {
            return back()->withErrors(['amount' => 'Saldo insuficiente.'])->withInput();
        }

        DB::transaction(function () use ($request) {
            WalletWithdrawal::create([
                'user_id'        => Auth::id(),
                'amount'         => $request->amount,
                'full_name'      => $request->full_name,
                'bank_name'      => $request->bank_name,
                'account_number' => $request->account_number,
                'status'         => 'pending',
            ]);

            // Descontar el saldo al solicitar (se devuelve si se rechaza)
            User::where('id', Auth::id())
                ->decrement('balance', $request->amount);
        });

        return back()->with('success', 'Solicitud de retiro enviada. Está pendiente de aprobación.');
    }

    public function approveWithdrawal($id)
    {
        $withdrawal = WalletWithdrawal::findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return back()->with('warning', 'Este retiro ya fue procesado.');
        }

        // Solo marcar como aprobado — el saldo ya se descontó al crear la solicitud
        $withdrawal->update(['status' => 'approved']);

        return back()->with('success', "Retiro #{$withdrawal->id} aprobado por \${$withdrawal->amount}.");
    }

    public function rejectWithdrawal($id)
    {
        $withdrawal = WalletWithdrawal::findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return back()->with('warning', 'Este retiro ya fue procesado.');
        }

        DB::transaction(function () use ($withdrawal) {
            $withdrawal->update(['status' => 'rejected']);

            // Devolver el saldo al usuario
            User::where('id', $withdrawal->user_id)
                ->increment('balance', $withdrawal->amount);
        });

        return back()->with('success', "Retiro #{$withdrawal->id} rechazado. Saldo devuelto al usuario.");
    }
}