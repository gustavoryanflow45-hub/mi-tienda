<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class SellerController extends Controller
{
    /**
     * Muestra el formulario de registro de tienda.
     */
    public function create()
    {
        return view('pages.shops-create');
    }

    /**
     * Procesa el registro: crea el User + la Shop.
     */
    public function register(Request $request)
    {
        // ── Validación ──────────────────────────────────────────────
        $request->validate([
            // Datos personales
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'confirmed', Password::min(8)],

            // Datos de la tienda
            'shop_name'             => ['required', 'string', 'max:255'],
            'shop_email'            => ['required', 'email', 'max:255'],
            'address'               => ['required', 'string', 'max:500'],

            // Imágenes del ID
            'id_front'              => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'id_back'               => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            // Mensajes en español
            'name.required'         => 'El nombre es obligatorio.',
            'email.required'        => 'El correo electrónico es obligatorio.',
            'email.unique'          => 'Este correo ya está registrado.',
            'password.required'     => 'La contraseña es obligatoria.',
            'password.confirmed'    => 'Las contraseñas no coinciden.',
            'shop_name.required'    => 'El nombre de la tienda es obligatorio.',
            'shop_email.required'   => 'El correo de la tienda es obligatorio.',
            'address.required'      => 'La dirección es obligatoria.',
            'id_front.required'     => 'La foto frontal del ID es obligatoria.',
            'id_front.image'        => 'El archivo frontal debe ser una imagen.',
            'id_back.required'      => 'La foto trasera del ID es obligatoria.',
            'id_back.image'         => 'El archivo trasero debe ser una imagen.',
        ]);

        // ── Subir imágenes del ID ───────────────────────────────────
        // Se guardan en storage/app/public/sellers/id/
        $idFrontPath = $request->file('id_front')
            ->store('sellers/id', 'public');

        $idBackPath  = $request->file('id_back')
            ->store('sellers/id', 'public');

        // ── Crear el usuario con user_type = seller ─────────────────
        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'user_type' => 'seller',   // campo de tu migración add_extra_fields_to_users
        ]);

        // ── Crear la tienda vinculada al usuario ────────────────────
        Shop::create([
            'user_id'        => $user->id,
            'name'           => $request->shop_name,
            'email'          => $request->shop_email,
            'address'        => $request->address,
            'id_front_image' => $idFrontPath,
            'id_back_image'  => $idBackPath,
            'status'         => 0, // pendiente de aprobación
        ]);

        // ── Iniciar sesión automáticamente ─────────────────────────
        Auth::login($user);

        // ── Redirigir con mensaje de éxito ─────────────────────────
        return redirect()->route('home')
            ->with('success', '¡Tienda registrada exitosamente! Tu solicitud está pendiente de aprobación.');
    }
}