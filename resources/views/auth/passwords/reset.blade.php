@extends('layouts.app')

@section('title', __('Nueva contraseña'))

@section('content')
<div style="min-height:70vh; display:flex; align-items:center; justify-content:center; background:#f5f6f8; padding:40px 16px;">
    <div style="background:#fff; border-radius:10px; box-shadow:0 2px 12px rgba(0,0,0,.08); padding:36px 32px; width:100%; max-width:420px;">

        <h2 style="font-size:1.1rem; font-weight:700; color:#222; margin-bottom:6px;">{{ __('Elige una nueva contraseña') }}</h2>
        <p style="font-size:.85rem; color:#888; margin-bottom:24px;">
            {{ __('Mínimo 8 caracteres. Al guardarla iniciarás sesión automáticamente.') }}
        </p>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div style="margin-bottom:16px;">
                <label style="font-size:.82rem; font-weight:600; color:#555; display:block; margin-bottom:5px;">{{ __('Correo electrónico') }}</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" required autofocus
                    style="width:100%; border:1px solid #dde2e8; border-radius:6px; padding:10px 14px; font-size:.88rem; outline:none; box-sizing:border-box;"
                    placeholder="tu@correo.com">
                @error('email')
                    <span style="color:#e74c3c; font-size:.78rem;">{{ $message }}</span>
                @enderror
            </div>

            <div style="margin-bottom:16px;">
                <label style="font-size:.82rem; font-weight:600; color:#555; display:block; margin-bottom:5px;">{{ __('Nueva contraseña') }}</label>
                <input type="password" name="password" required autocomplete="new-password"
                    style="width:100%; border:1px solid #dde2e8; border-radius:6px; padding:10px 14px; font-size:.88rem; outline:none; box-sizing:border-box;">
                @error('password')
                    <span style="color:#e74c3c; font-size:.78rem;">{{ $message }}</span>
                @enderror
            </div>

            <div style="margin-bottom:16px;">
                <label style="font-size:.82rem; font-weight:600; color:#555; display:block; margin-bottom:5px;">{{ __('Repite la contraseña') }}</label>
                <input type="password" name="password_confirmation" required autocomplete="new-password"
                    style="width:100%; border:1px solid #dde2e8; border-radius:6px; padding:10px 14px; font-size:.88rem; outline:none; box-sizing:border-box;">
            </div>

            <button type="submit"
                style="width:100%; background:#679941; color:#fff; border:none; border-radius:8px; padding:12px; font-size:.9rem; font-weight:700; cursor:pointer;">
                {{ __('Guardar contraseña') }}
            </button>
        </form>

        <div style="text-align:center; margin-top:20px; font-size:.83rem; color:#888;">
            <a href="{{ route('password.request') }}" style="color:#679941; text-decoration:none;">{{ __('¿El enlace caducó? Pide uno nuevo') }}</a>
        </div>

    </div>
</div>
@endsection
