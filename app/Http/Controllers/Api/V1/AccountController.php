<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\EmailRequest;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

/**
 * Lo que la app puede pedir sin sesión sobre una cuenta: reenviar la
 * verificación y el enlace para cambiar la contraseña. Los dos responden lo
 * mismo exista o no el correo, para no revelar qué cuentas hay.
 *
 * Los enlaces de los correos abren la web (son rutas firmadas de la
 * tienda): tras verificar o cambiar la contraseña, el usuario vuelve a la
 * app e inicia sesión.
 */
class AccountController extends Controller
{
    // ── POST /api/v1/auth/email/resend ───────────────────────────
    public function resendVerification(EmailRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if ($user && ! $user->isVerified() && ! $user->banned) {
            $user->notify(new VerifyEmailNotification);
        }

        return response()->json([
            'message' => __('Si la cuenta existe y no está verificada, te enviamos un nuevo enlace.'),
        ], 202);
    }

    // ── POST /api/v1/auth/password/forgot ────────────────────────
    public function forgotPassword(EmailRequest $request): JsonResponse
    {
        $status = Password::sendResetLink(['email' => $request->validated('email')]);

        // Lo único que se distingue es el límite del broker (60 s por correo).
        if ($status === Password::RESET_THROTTLED) {
            return response()->json(['message' => __($status), 'code' => 'too_many_requests'], 429);
        }

        return response()->json([
            'message' => __(PasswordResetController::SENT_MESSAGE),
        ], 202);
    }
}
