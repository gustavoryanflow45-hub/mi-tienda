<?php

namespace App\Http\Controllers;

use App\Models\SellerSettlement;
use App\Models\User;
use App\Models\WalletRecharge;
use App\Models\WalletWithdrawal;
use App\Services\ImageOptimizer;
use App\Services\SettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Billetera del propio usuario. La aprobación de recargas y retiros es del
 * panel: App\Http\Controllers\Admin\WalletController.
 */
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
        $recharges = WalletRecharge::where('user_id', Auth::id())->latest()->get();
        $withdrawals = WalletWithdrawal::where('user_id', Auth::id())->latest()->get();
        // Liquidaciones de ventas acreditadas por el admin (solo vendedores las tienen).
        $settlements = SellerSettlement::where('seller_id', Auth::id())->latest('settled_at')->get();
        // Parte del saldo que se retira sin aprobación: ya la aprobó el admin al liquidar.
        $withdrawable = $settlementService->withdrawableFor(Auth::user());

        return view('users.wallet', compact('recharges', 'withdrawals', 'settlements', 'withdrawable'));
    }

    /* ─────────────────────────────────────────
     | RECARGAS
    ───────────────────────────────────────── */

    public function recharge(Request $request, ImageOptimizer $images)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'network' => 'required|in:BEP20,TRC20,ERC20',
            'transaction_id' => 'required|string|max:255',
            'payment_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:'.config('images.max_upload_kb'),
        ]);

        $proofPath = null;
        if ($request->hasFile('payment_proof')) {
            // Un PDF pasa sin tocar; una foto del comprobante se reduce.
            $proofPath = $images->store($request->file('payment_proof'), 'wallet/proofs', 'payment_proof');
        }

        WalletRecharge::create([
            'user_id' => Auth::id(),
            'amount' => $request->amount,
            'network' => $request->network,
            'transaction_id' => $request->transaction_id,
            'payment_proof' => $proofPath,
            'approval' => 0,
        ]);

        return back()->with('success', __('Solicitud de recarga enviada. Está pendiente de aprobación.'));
    }

    /* ─────────────────────────────────────────
     | RETIROS
    ───────────────────────────────────────── */

    public function withdraw(Request $request, SettlementService $settlementService)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'full_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
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
                'user_id' => Auth::id(),
                'amount' => $request->amount,
                'full_name' => $request->full_name,
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'status' => $fromSettlement ? 'approved' : 'pending',
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
}
