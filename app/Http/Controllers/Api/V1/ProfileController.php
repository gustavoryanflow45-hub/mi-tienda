<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use App\Services\ImageOptimizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Perfil del usuario del token.
 *
 * El correo no se cambia desde la app: es el usuario del login y el destino
 * de los enlaces de verificación y de contraseña, así que un token robado no
 * debe poder quedarse con la cuenta cambiándolo. Se cambia en la web.
 */
class ProfileController extends Controller
{
    // ── GET /api/v1/profile ──────────────────────────────────────
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    // ── PATCH /api/v1/profile ────────────────────────────────────
    public function update(Request $request): UserResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $request->user()->fill($data)->save();

        return new UserResource($request->user());
    }

    // ── POST /api/v1/profile/avatar (multipart) ──────────────────
    public function avatar(Request $request, ImageOptimizer $images): UserResource
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('images.max_upload_kb')],
        ]);

        $user = $request->user();
        // Reducida y en WebP, como el avatar de la web (config/images.php).
        $user->avatar = $images->store($request->file('avatar'), 'avatars', 'avatar');
        $user->save();

        return new UserResource($user);
    }

    // ── PUT /api/v1/profile/password ─────────────────────────────
    // Cierra las sesiones de los demás dispositivos; este sigue con su token.
    public function password(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('La contraseña actual no es correcta.'),
            ]);
        }

        $user->forceFill(['password' => Hash::make($request->password)])->save();
        $user->tokens()->whereKeyNot($user->currentAccessToken()->getKey())->delete();

        return response()->json(['message' => __('Contraseña actualizada correctamente.')]);
    }
}
