<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerificationController extends Controller
{
    public function __construct()
    {
        // 'verify' NO lleva auth: el enlace se abre desde el correo, a menudo
        // en otro navegador donde no hay sesión iniciada. La firma del enlace
        // es lo que autoriza la operación.
        $this->middleware('auth')->except('verify');
    }

    /** GET /email/verify — explica qué hacer y permite reenviar. */
    public function notice()
    {
        if (Auth::user()->isVerified()) {
            return redirect()->route('dashboard')
                ->with('success', 'Tu correo ya está verificado.');
        }

        return view('auth.verify-email');
    }

    /** GET /email/verify/{id}/{hash} — enlace firmado del correo. */
    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);

        // El hash ata el enlace al correo actual: si el usuario cambió de
        // correo después de pedirlo, el enlace viejo deja de servir.
        if (! hash_equals($hash, sha1($user->email))) {
            abort(403, 'El enlace de verificación no es válido.');
        }

        if ($user->isVerified()) {
            return redirect()->route('dashboard')
                ->with('success', 'Tu correo ya estaba verificado.');
        }

        $user->email_verified_at = now();
        $user->email_verified = 1;
        $user->verification_code = null;
        $user->save();

        // Si abrió el enlace sin sesión, lo dejamos dentro directamente.
        if (! Auth::check()) {
            Auth::login($user);
        }

        return redirect()->route('dashboard')
            ->with('success', '¡Correo verificado correctamente! Ya tienes acceso completo.');
    }

    /** POST /email/resend — vuelve a enviar el enlace. */
    public function resend(Request $request)
    {
        $user = Auth::user();

        if ($user->isVerified()) {
            return redirect()->route('dashboard')
                ->with('success', 'Tu correo ya está verificado.');
        }

        $user->notify(new VerifyEmailNotification);

        return back()->with('success', 'Te enviamos un nuevo enlace de verificación a '.$user->email.'.');
    }
}
