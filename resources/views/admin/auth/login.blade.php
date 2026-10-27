{{-- resources/views/admin/auth/login.blade.php --}}
@extends('layouts.app')

@section('title', __('Acceso al panel de administración'))

@section('content')
<section class="py-5 my-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <i class="las la-user-shield" style="font-size:3rem;color:#679941;"></i>
                            <h4 class="fw-700">{{ __('Panel de administración') }}</h4>
                            <p class="text-muted fs-14">{{ __('Acceso restringido al personal autorizado.') }}</p>
                        </div>

                        <form method="POST" action="{{ route('admin.login.attempt') }}">
                            @csrf
                            <div class="form-group">
                                <label class="fw-600 fs-14" for="admin-email">{{ __('Correo electrónico') }}</label>
                                <input type="email" name="email" id="admin-email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}" required autofocus autocomplete="username">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="fw-600 fs-14" for="admin-password">{{ __('Contraseña') }}</label>
                                <input type="password" name="password" id="admin-password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       required autocomplete="current-password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary btn-block fw-600">
                                {{ __('Iniciar sesión') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
