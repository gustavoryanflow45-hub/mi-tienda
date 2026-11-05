<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\CustomerRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request, CustomerRegistrationService $registration)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users',
            'password'   => 'required|min:6|confirmed',
        ]);

        // Misma alta que la API (CustomerRegistrationService): user_type
        // se asigna allí, nunca desde el request.
        $user = $registration->register([
            'name'     => $request->first_name . ' ' . $request->last_name,
            'email'    => $request->email,
            'password' => $request->password,
            'phone'    => $request->phone,
        ]);

        Auth::login($user);
        return redirect('/');
    }
}