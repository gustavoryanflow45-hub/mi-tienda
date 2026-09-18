<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Recuperación de contraseña sobre el broker de Laravel: el token vive en
 * password_reset_tokens y caduca según config/auth.php (60 min). Las cuatro
 * rutas van bajo 'guest': quien ya tiene sesión cambia la clave desde su
 * perfil.
 */
class PasswordResetController extends Controller
{
    /**
     * Respuesta única al pedir el enlace, exista o no la cuenta: si dijera
     * "ese correo no está registrado" serviría para enumerar usuarios.
     */
    private const SENT_MESSAGE = 'Si existe una cuenta con ese correo, recibirás el enlace en breve.';

    /** GET /password/reset — formulario para pedir el enlace. */
    public function request()
    {
        return view('auth.passwords.email');
    }

    /** POST /password/email — envía el enlace (throttle en la ruta). */
    public function email(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        // RESET_THROTTLED sí se informa: el usuario real acaba de pedirlo y
        // se pregunta por qué no le llega otro; no revela si la cuenta existe.
        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput()->withErrors(['email' => __($status)]);
        }

        return back()->with('status', self::SENT_MESSAGE);
    }

    /** GET /password/reset/{token} — formulario de nueva contraseña. */
    public function reset(Request $request, string $token)
    {
        return view('auth.passwords.reset', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /** POST /password/reset — guarda la nueva contraseña e inicia sesión. */
    public function update(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                // La persona acaba de demostrar que controla el correo: entra directo.
                Auth::login($user);
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        }

        return redirect()->route('dashboard')
            ->with('success', 'Contraseña actualizada. Ya has iniciado sesión.');
    }
}
