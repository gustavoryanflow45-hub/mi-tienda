<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SettlementResource;
use App\Http\Resources\V1\WalletMovementResource;
use App\Http\Resources\V1\WalletRechargeResource;
use App\Http\Resources\V1\WalletWithdrawalResource;
use App\Models\SellerSettlement;
use App\Models\WalletRecharge;
use App\Models\WalletWithdrawal;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Billetera del usuario del token, con la misma lógica que /wallet en la
 * web (WalletService): recargas y retiros quedan pendientes hasta que el
 * panel los aprueba; un retiro descuenta el saldo en el acto (en una
 * transacción con el saldo bloqueado) y se devuelve si se rechaza.
 *
 * A diferencia de la web, la app cobra la comisión de red
 * (wallet.app_network_fee): una recarga de X acredita X − fee y un retiro
 * de X descuenta X y se transfieren X − fee.
 *
 * Aprobar y rechazar es solo del panel: aquí no hay rutas para eso. Quién ve
 * cada solicitud lo deciden WalletRechargePolicy / WalletWithdrawalPolicy
 * (la de otro usuario es 404); los listados además filtran por user_id. Los
 * POST llevan Idempotency-Key (api.idempotent) contra el doble envío.
 */
class WalletController extends Controller
{
    /** Redes aceptadas para recargar (las del formulario web). */
    public const NETWORKS = ['BEP20', 'TRC20', 'ERC20'];

    // ── GET /api/v1/wallet ───────────────────────────────────────
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json(['data' => [
            'balance' => round((float) $user->balance, 2),
            'currency' => 'USD',
            // Comisión de red que se descuenta de cada recarga y retiro de la app.
            'network_fee' => self::fee(),
            'pending_recharges' => WalletRecharge::where('user_id', $user->id)->where('approval', 0)->count(),
            'pending_withdrawals' => WalletWithdrawal::where('user_id', $user->id)->where('status', 'pending')->count(),
            'min_withdrawal' => round((float) config('wallet.min_withdrawal', 1), 2),
            'recharge_networks' => self::NETWORKS,
            'max_upload_kb' => (int) config('images.max_upload_kb'),
        ]]);
    }

    // ── GET /api/v1/wallet/movements ─────────────────────────────
    // Recargas, retiros y liquidaciones en una sola lista, del más nuevo al
    // más viejo. Solo mueve el saldo lo `approved` (un retiro rechazado ya
    // devolvió su importe; uno pendiente ya lo descontó).
    public function movements(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()->id;

        $recharges = DB::table('wallet_recharges')->where('user_id', $userId)->selectRaw(
            "'recharge' AS type, id, amount - fee AS amount, CASE WHEN approval = 1 THEN 'approved' WHEN approval = 0 THEN 'pending' ELSE 'rejected' END AS status, created_at AS occurred_at"
        );
        $withdrawals = DB::table('wallet_withdrawals')->where('user_id', $userId)->selectRaw(
            "'withdrawal' AS type, id, amount, CASE WHEN status IN ('approved', 'rejected') THEN status ELSE 'pending' END AS status, created_at AS occurred_at"
        );
        $settlements = DB::table('seller_settlements')->where('seller_id', $userId)->selectRaw(
            "'settlement' AS type, id, net_amount AS amount, 'approved' AS status, settled_at AS occurred_at"
        );

        return WalletMovementResource::collection(
            DB::query()
                ->fromSub($recharges->unionAll($withdrawals)->unionAll($settlements), 'movements')
                ->orderByDesc('occurred_at')
                ->orderByDesc('id')
                ->paginate($this->perPage($request))
                ->withQueryString()
        );
    }

    // ── GET /api/v1/wallet/recharges ─────────────────────────────
    public function recharges(Request $request): AnonymousResourceCollection
    {
        return WalletRechargeResource::collection(
            WalletRecharge::where('user_id', $request->user()->id)->latest()->orderByDesc('id')
                ->paginate($this->perPage($request))->withQueryString()
        );
    }

    // ── GET /api/v1/wallet/recharges/{id} ────────────────────────
    public function showRecharge(int $recharge): WalletRechargeResource
    {
        $model = WalletRecharge::findOrFail($recharge);
        $this->authorize('view', $model);

        return new WalletRechargeResource($model);
    }

    // ── POST /api/v1/wallet/recharges (multipart) ────────────────
    public function recharge(Request $request, WalletService $wallet): JsonResponse
    {
        $this->authorize('create', WalletRecharge::class);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:100000'],
            'network' => ['required', 'in:'.implode(',', self::NETWORKS)],
            'transaction_id' => ['required', 'string', 'max:255'],
            // Obligatorio en la app: es lo que el panel revisa para aprobar.
            'payment_proof' => [
                'required', 'file', 'mimes:jpg,jpeg,png,webp,pdf',
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf',
                'max:'.config('images.max_upload_kb'),
            ],
        ]);

        $recharge = $wallet->requestRecharge($request->user(), $data, $request->file('payment_proof'), self::fee());

        return (new WalletRechargeResource($recharge->refresh()))
            ->additional(['message' => __('Solicitud de recarga enviada. Está pendiente de aprobación.')])
            ->response()
            ->setStatusCode(201);
    }

    // ── GET /api/v1/wallet/withdrawals ───────────────────────────
    public function withdrawals(Request $request): AnonymousResourceCollection
    {
        return WalletWithdrawalResource::collection(
            WalletWithdrawal::where('user_id', $request->user()->id)->latest()->orderByDesc('id')
                ->paginate($this->perPage($request))->withQueryString()
        );
    }

    // ── GET /api/v1/wallet/withdrawals/{id} ──────────────────────
    public function showWithdrawal(int $withdrawal): WalletWithdrawalResource
    {
        $model = WalletWithdrawal::findOrFail($withdrawal);
        $this->authorize('view', $model);

        return new WalletWithdrawalResource($model);
    }

    // ── POST /api/v1/wallet/withdrawals ──────────────────────────
    public function withdraw(Request $request, WalletService $wallet): JsonResponse
    {
        $this->authorize('create', WalletWithdrawal::class);

        $data = $request->validate([
            // El mínimo (wallet.min_withdrawal) y el saldo los comprueba WalletService.
            'amount' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'full_name' => ['required', 'string', 'min:3', 'max:255'],
            'bank_name' => ['required', 'string', 'min:2', 'max:255'],
            // Números de cuenta locales o IBAN: letras, dígitos, espacios y guiones.
            'account_number' => ['required', 'string', 'regex:/^[A-Za-z0-9 -]{4,34}$/'],
        ]);

        // Bajo el mínimo, sin cubrir la comisión o sin saldo: ValidationException sobre 'amount' (422).
        $withdrawal = $wallet->requestWithdrawal($request->user(), $data, self::fee());

        return (new WalletWithdrawalResource($withdrawal->refresh()))
            ->additional(['message' => __('Solicitud de retiro enviada. Está pendiente de aprobación.')])
            ->response()
            ->setStatusCode(201);
    }

    // ── GET /api/v1/wallet/settlements ───────────────────────────
    // Liquidaciones de ventas acreditadas (solo los vendedores tienen).
    public function settlements(Request $request): AnonymousResourceCollection
    {
        return SettlementResource::collection(
            SellerSettlement::where('seller_id', $request->user()->id)->latest('settled_at')->orderByDesc('id')
                ->paginate($this->perPage($request))->withQueryString()
        );
    }

    /** Comisión de red de la app (config wallet.app_network_fee). */
    public static function fee(): float
    {
        return round(max(0, (float) config('wallet.app_network_fee', 1)), 2);
    }

    private function perPage(Request $request): int
    {
        return (int) ($request->validate(['per_page' => ['nullable', 'integer', 'min:1', 'max:50']])['per_page'] ?? 15);
    }
}
