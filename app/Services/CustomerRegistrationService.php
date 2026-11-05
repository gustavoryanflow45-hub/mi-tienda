<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Hash;

/**
 * Alta de un comprador. La usan el registro web (Auth\RegisterController) y
 * la API (Api\V1\AuthController@register): misma cuenta, mismo correo de
 * verificación.
 *
 * user_type no es fillable (ver User::$fillable): se asigna aquí, nunca
 * desde lo que manda el cliente.
 */
class CustomerRegistrationService
{
    /** @param  array{name: string, email: string, password: string, phone?: string|null}  $data */
    public function register(array $data): User
    {
        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
        ]);
        $user->user_type = 'customer';
        $user->save();

        $user->notify(new VerifyEmailNotification);

        return $user;
    }
}
