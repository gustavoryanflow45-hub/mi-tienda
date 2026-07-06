@extends('layouts.app')

@section('title', 'Restablecer Contraseña')

@section('content')
<div style="min-height:70vh; display:flex; align-items:center; justify-content:center; background:#f5f6f8; padding:40px 16px;">
    <div style="background:#fff; border-radius:10px; box-shadow:0 2px 12px rgba(0,0,0,.08); padding:36px 32px; width:100%; max-width:420px;">

        <h2 style="font-size:1.1rem; font-weight:700; color:#222; margin-bottom:6px;">Restablecer contraseña</h2>
        <p style="font-size:.85rem; color:#888; margin-bottom:24px;">
            Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.
        </p>

        @if(session('status'))
            <div style="background:#d4edda; color:#155724; border-radius:6px; padding:10px 14px; font-size:.84rem; margin-bottom:16px;">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div style="margin-bottom:16px;">
                <label style="font-size:.82rem; font-weight:600; color:#555; display:block; margin-bottom:5px;">Correo electrónico</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                    style="width:100%; border:1px solid #dde2e8; border-radius:6px; padding:10px 14px; font-size:.88rem; outline:none; box-sizing:border-box;"
                    placeholder="tu@correo.com">
                @error('email')
                    <span style="color:#e74c3c; font-size:.78rem;">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit"
                style="width:100%; background:#679941; color:#fff; border:none; border-radius:8px; padding:12px; font-size:.9rem; font-weight:700; cursor:pointer;">
                Enviar enlace de restablecimiento
            </button>
        </form>

        <div style="text-align:center; margin-top:20px; font-size:.83rem; color:#888;">
            <a href="{{ route('login') }}" style="color:#679941; text-decoration:none;">← Volver al inicio de sesión</a>
        </div>

    </div>
</div>
@endsection
