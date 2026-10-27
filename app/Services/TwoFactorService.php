<?php

namespace App\Services;

use App\Models\AdminTwoFactor;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Segundo factor TOTP (RFC 6238) de los admin: Google Authenticator, Authy,
 * 1Password… Los códigos de recuperación se guardan hasheados y se muestran
 * una sola vez, al inscribirse.
 */
class TwoFactorService
{
    public const RECOVERY_CODES = 8;

    /** Pasos de 30 s aceptados a cada lado del actual (reloj del móvil). */
    public const WINDOW = 1;

    public function __construct(private Google2FA $google2fa) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function otpauthUrl(User $user, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(config('app.name'), $user->email, $secret);
    }

    public function qrSvg(string $otpauthUrl): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd));

        return $writer->writeString($otpauthUrl);
    }

    /**
     * Cierra la inscripción si el código corresponde al secreto que el admin
     * acaba de escanear. Devuelve los códigos de recuperación en claro (para
     * mostrarlos una vez) o null si el código no vale.
     */
    public function confirm(User $user, string $secret, string $code): ?array
    {
        $timestep = $this->matchingTimestep($secret, $code, null);

        if ($timestep === false) {
            return null;
        }

        $codes = $this->generateRecoveryCodes();

        AdminTwoFactor::updateOrCreate(['user_id' => $user->id], [
            'secret' => $secret,
            'recovery_codes' => array_map(fn ($c) => Hash::make($c), $codes),
            'last_used_timestep' => $timestep,
            'confirmed_at' => now(),
        ]);

        return $codes;
    }

    /**
     * Valida un código TOTP. Bloquea la fila para que dos peticiones
     * simultáneas no acepten el mismo código.
     */
    public function verifyCode(AdminTwoFactor $twoFactor, string $code): bool
    {
        return DB::transaction(function () use ($twoFactor, $code) {
            $row = AdminTwoFactor::lockForUpdate()->find($twoFactor->id);

            if (! $row?->isConfirmed()) {
                return false;
            }

            $timestep = $this->matchingTimestep($row->secret, $code, $row->last_used_timestep);

            if ($timestep === false) {
                return false;
            }

            $row->forceFill(['last_used_timestep' => $timestep])->save();

            return true;
        });
    }

    /** Consume un código de recuperación: cada uno vale una sola vez. */
    public function useRecoveryCode(AdminTwoFactor $twoFactor, string $code): bool
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return false;
        }

        return DB::transaction(function () use ($twoFactor, $code) {
            $row = AdminTwoFactor::lockForUpdate()->find($twoFactor->id);

            if (! $row?->isConfirmed()) {
                return false;
            }

            $hashes = $row->recovery_codes ?? [];

            foreach ($hashes as $i => $hash) {
                if (Hash::check($code, $hash)) {
                    unset($hashes[$i]);
                    $row->forceFill(['recovery_codes' => array_values($hashes)])->save();
                    $twoFactor->setRawAttributes($row->getAttributes(), true);

                    return true;
                }
            }

            return false;
        });
    }

    /** @return array<int, string> p. ej. "K3J9Q-7XW2M" */
    protected function generateRecoveryCodes(): array
    {
        return collect(range(1, self::RECOVERY_CODES))
            ->map(fn () => strtoupper(Str::random(5).'-'.Str::random(5)))
            ->all();
    }

    /** Paso de 30 s en que el código es válido y posterior a $after, o false. */
    protected function matchingTimestep(string $secret, string $code, ?int $after): int|false
    {
        $code = preg_replace('/\s+/', '', $code);

        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        // Con un $oldTimestamp entero la librería devuelve el paso, no un bool.
        return $this->google2fa->verifyKeyNewer($secret, $code, $after ?? 0, self::WINDOW);
    }
}
