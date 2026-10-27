<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Inscripción TOTP de un admin. La gestiona TwoFactorService; el estado
 * "este admin ya pasó el 2FA" vive en la sesión (EnsureAdminTwoFactor).
 */
class AdminTwoFactor extends Model
{
    protected $fillable = [
        'user_id',
        'secret',
        'recovery_codes',
        'last_used_timestep',
        'confirmed_at',
    ];

    protected $hidden = [
        'secret',
        'recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'recovery_codes' => 'array',
            'last_used_timestep' => 'integer',
            'confirmed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    public function remainingRecoveryCodes(): int
    {
        return count($this->recovery_codes ?? []);
    }
}
