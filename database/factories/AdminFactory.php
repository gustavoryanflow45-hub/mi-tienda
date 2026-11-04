<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Support\AdminPermissions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin>
 */
class AdminFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
        ];
    }

    /**
     * Por defecto, super-admin: los tests de cada sección prueban la sección,
     * no los permisos. Los de permisos usan ->role('soporte') y similares.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Admin $admin) {
            if (! $admin->roles()->exists()) {
                $admin->assignRole(AdminPermissions::SUPER_ADMIN);
            }
        });
    }

    /** Con este rol (y solo este) en lugar de super-admin. */
    public function role(string $role): static
    {
        return $this->afterCreating(fn (Admin $admin) => $admin->syncRoles([$role]));
    }
}
