<?php

namespace App\Http\Controllers;

use App\Models\SellerSettlement;
use App\Models\WalletRecharge;
use App\Models\WalletWithdrawal;
use App\Services\SettlementService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Billetera del propio usuario. La lógica está en WalletService; la
 * aprobación de recargas y retiros es del panel
 * (App\Http\Controllers\Admin\WalletController).
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

    public function recharge(Request $request, WalletService $wallet)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'network' => 'required|in:BEP20,TRC20,ERC20',
            'transaction_id' => 'required|string|max:255',
            'payment_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:'.config('images.max_upload_kb'),
        ]);

        $wallet->requestRecharge($request->user(), $data, $request->file('payment_proof'));

        return back()->with('success', __('Solicitud de recarga enviada. Está pendiente de aprobación.'));
    }

    /* ─────────────────────────────────────────
     | RETIROS
    ───────────────────────────────────────── */

    public function withdraw(Request $request, WalletService $wallet)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'full_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
        ]);

        // Sin saldo suficiente lanza una ValidationException sobre 'amount'.
        $withdrawal = $wallet->requestWithdrawal($request->user(), $data);

        return back()->with('success', $withdrawal->from_settlement
            ? __('Retiro aprobado: proviene de ventas ya liquidadas, no necesita otra aprobación.')
            : __('Solicitud de retiro enviada. Está pendiente de aprobación.'));
    }
}
