<?php

namespace Tests\Concerns;

use App\Http\Middleware\EnsureAdminTwoFactor;
use App\Models\Admin;

/**
 * Actúa como un admin que ya pasó el segundo factor en esta sesión. Los
 * tests del propio 2FA (AdminTwoFactorTest) no lo usan: recorren el flujo.
 */
trait PassesAdminTwoFactor
{
    protected function actingAsAdmin(Admin $admin): static
    {
        return $this->actingAs($admin, 'admin')
            ->withSession([EnsureAdminTwoFactor::SESSION_KEY => $admin->id]);
    }
}
