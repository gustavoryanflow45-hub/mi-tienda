@extends('layouts.app')

@section('title', 'Mi Perfil')

@section('content')
<div style="background:#f2f3f8; padding:30px 0 60px; min-height:70vh;">
    <div class="container" style="max-width:640px;">

        <h2 style="font-size:1.15rem; font-weight:700; color:#222; margin-bottom:20px;">
            <i class="las la-user-circle" style="color:#679941;"></i> Mi Perfil
        </h2>

        @if(session('success'))
            <div style="background:#d4edda; color:#155724; border-radius:6px; padding:10px 14px; font-size:.84rem; margin-bottom:16px;">
                {{ session('success') }}
            </div>
        @endif

        <div style="background:#fff; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,.07); padding:28px;">

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div style="margin-bottom:16px;">
                    <label style="font-size:.82rem; font-weight:600; color:#555; display:block; margin-bottom:5px;">Nombre completo</label>
                    <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required
                        style="width:100%; border:1px solid #dde2e8; border-radius:6px; padding:10px 14px; font-size:.88rem; outline:none; box-sizing:border-box;">
                    @error('name') <span style="color:#e74c3c; font-size:.78rem;">{{ $message }}</span> @enderror
                </div>

                <div style="margin-bottom:16px;">
                    <label style="font-size:.82rem; font-weight:600; color:#555; display:block; margin-bottom:5px;">Correo electrónico</label>
                    <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required
                        style="width:100%; border:1px solid #dde2e8; border-radius:6px; padding:10px 14px; font-size:.88rem; outline:none; box-sizing:border-box;">
                    @error('email') <span style="color:#e74c3c; font-size:.78rem;">{{ $message }}</span> @enderror
                </div>

                <div style="margin-bottom:16px;">
                    <label style="font-size:.82rem; font-weight:600; color:#555; display:block; margin-bottom:5px;">Teléfono</label>
                    <input type="text" name="phone" value="{{ old('phone', auth()->user()->phone) }}"
                        style="width:100%; border:1px solid #dde2e8; border-radius:6px; padding:10px 14px; font-size:.88rem; outline:none; box-sizing:border-box;">
                </div>

                <hr style="border:none; border-top:1px solid #f0f0f0; margin:20px 0;">
                <p style="font-size:.82rem; color:#888; margin-bottom:16px;">Dejar en blanco para mantener la contraseña actual.</p>

                <div style="margin-bottom:16px;">
                    <label style="font-size:.82rem; font-weight:600; color:#555; display:block; margin-bottom:5px;">Nueva contraseña</label>
                    <input type="password" name="password"
                        style="width:100%; border:1px solid #dde2e8; border-radius:6px; padding:10px 14px; font-size:.88rem; outline:none; box-sizing:border-box;">
                    @error('password') <span style="color:#e74c3c; font-size:.78rem;">{{ $message }}</span> @enderror
                </div>

                <div style="margin-bottom:24px;">
                    <label style="font-size:.82rem; font-weight:600; color:#555; display:block; margin-bottom:5px;">Confirmar nueva contraseña</label>
                    <input type="password" name="password_confirmation"
                        style="width:100%; border:1px solid #dde2e8; border-radius:6px; padding:10px 14px; font-size:.88rem; outline:none; box-sizing:border-box;">
                </div>

                <button type="submit"
                    style="background:#679941; color:#fff; border:none; border-radius:8px; padding:12px 28px; font-size:.9rem; font-weight:700; cursor:pointer;">
                    Guardar cambios
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
