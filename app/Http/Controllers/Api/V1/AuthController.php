<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\V1\TokenResource;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Services\ApiTokenService;
use App\Services\CustomerRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

/**
 * Autenticación de la app: alta, login por dispositivo, perfil propio,
 * rotación y cierre de sesión. Tokens de Sanctum vía ApiTokenService.
 *
 * Solo se emiten tokens a cuentas con el correo verificado y no bloqueadas.
 */
class AuthController extends Controller
{
    /** Hash con el que se compara cuando el correo no existe: mismo tiempo de respuesta. */
    private const DUMMY_HASH = '$2y$12$Pm63acy5DUhR93INumRqaO4hcgFZ53sJRzvQ02AHytMkJ.ZempSI6';

    // ── POST /api/v1/auth/register ───────────────────────────────
    public function register(RegisterRequest $request, CustomerRegistrationService $registration): JsonResponse
    {
        $user = $registration->register($request->validated());

        // Sin token: primero hay que verificar el correo (enlace en el email).
        return (new UserResource($user))
            ->additional(['message' => __('Cuenta creada. Te enviamos un enlace para verificar tu correo; después podrás iniciar sesión.')])
            ->response()
            ->setStatusCode(201);
    }

    // ── POST /api/v1/auth/login ──────────────────────────────────
    public function login(LoginRequest $request, ApiTokenService $tokens): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();
        $valid = Hash::check($request->validated('password'), $user?->password ?? self::DUMMY_HASH);

        if (! $user || ! $valid) {
            return $this->error(__('Correo o contraseña incorrectos.'), 'invalid_credentials', 401);
        }

        if ($user->banned) {
            return $this->error(__('Esta cuenta está bloqueada. Contacta con soporte.'), 'account_disabled', 403);
        }

        if (! $user->isVerified()) {
            return $this->error(__('Verifica tu correo antes de iniciar sesión. Puedes pedir otro enlace.'), 'email_not_verified', 403);
        }

        $token = $tokens->issue($user, $request->validated('device_id'), $request->validated('device_name'));

        return (new TokenResource($token))->response();
    }

    // ── GET /api/v1/auth/me ──────────────────────────────────────
    public function me(Request $request): UserResource
    {
        return (new UserResource($request->user()))->additional([
            'meta' => ['abilities' => $request->user()->currentAccessToken()->abilities],
        ]);
    }

    // ── POST /api/v1/auth/token/rotate ───────────────────────────
    public function rotate(Request $request, ApiTokenService $tokens): JsonResponse
    {
        $token = $tokens->rotate($request->user(), $request->user()->currentAccessToken());

        return (new TokenResource($token))->response();
    }

    // ── POST /api/v1/auth/logout ─────────────────────────────────
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    protected function error(string $message, string $code, int $status): JsonResponse
    {
        return response()->json(['message' => $message, 'code' => $code], $status);
    }
}
