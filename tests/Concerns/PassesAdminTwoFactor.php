<?php

namespace Tests\Concerns;

use App\Http\Middleware\EnsureAdminTwoFactor;
use App\Models\User;

/**
 * Actúa como un admin que ya pasó el segundo factor en esta sesión. Los
 * tests del propio 2FA (AdminTwoFactorTest) no lo usan: recorren el flujo.
 */
trait PassesAdminTwoFactor
{
    protected function actingAsAdmin(User $admin): static
    {
        return $this->actingAs($admin)
            ->withSession([EnsureAdminTwoFactor::SESSION_KEY => $admin->id]);
    }
}
