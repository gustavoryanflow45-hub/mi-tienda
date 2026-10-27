<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAdminTwoFactor;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Segundo factor del panel admin.
 *
 * - challenge/verify: tras el login, antes de cualquier ruta del panel.
 * - setup/confirm: alta del autenticador. Solo con el enlace firmado que
 *   genera `php artisan admin:2fa-invite`: la contraseña sola nunca permite
 *   inscribir un autenticador, así que quien la robe no puede adelantarse.
 */
class TwoFactorController extends Controller
{
    /** Secreto en curso de inscripción; no toca la BD hasta confirmarlo. */
    public const SETUP_SESSION_KEY = 'admin_two_factor_setup_secret';

    /**
     * Fallos de código que cierran la sesión. Se cuentan por admin, no por
     * sesión, así que volver a iniciar sesión no reinicia la cuenta: hasta
     * que pasen LOCKOUT_SECONDS no hay más intentos.
     */
    public const MAX_FAILURES = 10;

    public const LOCKOUT_SECONDS = 900;

    public function __construct(private TwoFactorService $twoFactor) {}

    // ── GET /admin/two-factor ────────────────────────────────────
    public function challenge(Request $request)
    {
        if ($this->passed($request)) {
            return redirect()->intended(route('admin.home'));
        }

        $enrolled = (bool) $request->user()->twoFactor?->isConfirmed();

        return view('admin.two-factor.challenge', compact('enrolled'));
    }

    // ── POST /admin/two-factor ───────────────────────────────────
    public function verify(Request $request)
    {
        $data = $request->validate([
            'code' => 'nullable|string|max:16|required_without:recovery_code',
            'recovery_code' => 'nullable|string|max:32',
        ]);

        $user = $request->user();
        $record = $user->twoFactor;

        if (! $record?->isConfirmed()) {
            return redirect()->route('admin.two-factor.challenge');
        }

        if (RateLimiter::tooManyAttempts($this->failuresKey($user->id), self::MAX_FAILURES)) {
            return $this->lockOut($request);
        }

        $usedRecovery = false;

        if (! empty($data['code'])) {
            $valid = $this->twoFactor->verifyCode($record, $data['code']);
        } else {
            $valid = $usedRecovery = $this->twoFactor->useRecoveryCode($record, $data['recovery_code']);
        }

        if (! $valid) {
            RateLimiter::hit($this->failuresKey($user->id), self::LOCKOUT_SECONDS);

            if (RateLimiter::tooManyAttempts($this->failuresKey($user->id), self::MAX_FAILURES)) {
                return $this->lockOut($request);
            }

            return back()->withErrors(['code' => __('El código no es válido o ya fue usado.')]);
        }

        RateLimiter::clear($this->failuresKey($user->id));
        $this->markPassed($request);

        if ($usedRecovery) {
            $request->session()->flash('warning', __('Entraste con un código de recuperación. Te quedan :count; pide un nuevo enlace de alta si se acaban.', [
                'count' => $record->remainingRecoveryCodes(),
            ]));
        }

        return redirect()->intended(route('admin.home'));
    }

    // ── GET /admin/two-factor/setup (firmada) ────────────────────
    public function setup(Request $request)
    {
        $this->ensureInvitedUser($request);

        if ($request->user()->twoFactor?->isConfirmed()) {
            return redirect()->route('admin.two-factor.challenge')
                ->with('warning', __('Tu cuenta ya tiene el segundo factor activo. Para cambiar de dispositivo pide un enlace nuevo con --reset.'));
        }

        $secret = $request->session()->get(self::SETUP_SESSION_KEY);

        if (! $secret) {
            $secret = $this->twoFactor->generateSecret();
            $request->session()->put(self::SETUP_SESSION_KEY, $secret);
        }

        $qr = $this->twoFactor->qrSvg($this->twoFactor->otpauthUrl($request->user(), $secret));

        return view('admin.two-factor.setup', [
            'qr' => $qr,
            'secret' => $secret,
            'action' => $request->fullUrl(),
        ]);
    }

    // ── POST /admin/two-factor/setup (firmada) ───────────────────
    public function confirm(Request $request)
    {
        $this->ensureInvitedUser($request);

        $request->validate(['code' => 'required|string|max:16']);

        $user = $request->user();
        $secret = $request->session()->get(self::SETUP_SESSION_KEY);

        if ($user->twoFactor?->isConfirmed() || ! $secret) {
            return redirect()->route('admin.two-factor.challenge');
        }

        $codes = $this->twoFactor->confirm($user, $secret, $request->input('code'));

        if ($codes === null) {
            return back()->withErrors(['code' => __('El código no es válido o ya fue usado.')]);
        }

        $request->session()->forget(self::SETUP_SESSION_KEY);
        $this->markPassed($request);

        // Se muestran aquí y no se guardan en claro en ningún sitio.
        return response()->view('admin.two-factor.recovery-codes', compact('codes'))
            ->header('Cache-Control', 'no-store');
    }

    protected function failuresKey(int $userId): string
    {
        return 'admin-2fa-failures:'.$userId;
    }

    /** Demasiados códigos fallidos: fuera de la sesión, sin más intentos. */
    protected function lockOut(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->withErrors([
            'email' => __('Demasiados códigos incorrectos. Por seguridad se cerró la sesión; espera 15 minutos antes de volver a intentarlo.'),
        ]);
    }

    protected function passed(Request $request): bool
    {
        return (int) $request->session()->get(EnsureAdminTwoFactor::SESSION_KEY) === (int) $request->user()->id;
    }

    protected function markPassed(Request $request): void
    {
        $request->session()->regenerate();
        $request->session()->put(EnsureAdminTwoFactor::SESSION_KEY, $request->user()->id);
    }

    /** El enlace firmado es para una cuenta concreta. */
    protected function ensureInvitedUser(Request $request): void
    {
        if ((int) $request->query('user') !== (int) $request->user()->id) {
            abort(403, __('Este enlace de alta es para otra cuenta.'));
        }
    }
}
