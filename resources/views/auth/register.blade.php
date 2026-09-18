{{-- resources/views/auth/register.blade.php --}}
@extends('layouts.app')

@section('title', 'Registration')

@section('content')
<section class="py-5 my-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <a href="{{ route('home') }}">
                                <img src="{{ asset('assets/img/logo.png') }}"
                                     alt="Woot" height="40" class="mb-3">
                            </a>
                            <h4 class="fw-700">{{ __('Crear cuenta') }}</h4>
                            <p class="text-muted fs-14">{{ __('Únete a Woot y empieza a comprar') }}</p>
                        </div>

                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('register') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="fw-600 fs-14">{{ __('Nombre') }}</label>
                                        <input type="text" name="first_name"
                                               class="form-control @error('first_name') is-invalid @enderror"
                                               placeholder="{{ __('Nombre') }}"
                                               value="{{ old('first_name') }}" required>
                                        @error('first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="fw-600 fs-14">{{ __('Apellido') }}</label>
                                        <input type="text" name="last_name"
                                               class="form-control @error('last_name') is-invalid @enderror"
                                               placeholder="{{ __('Apellido') }}"
                                               value="{{ old('last_name') }}" required>
                                        @error('last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="fw-600 fs-14">{{ __('Correo electrónico') }}</label>
                                <input type="email" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       placeholder="{{ __('Escribe tu correo') }}"
                                       value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="fw-600 fs-14">{{ __('Teléfono') }}</label>
                                <input type="tel" name="phone"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       placeholder="{{ __('Número de teléfono') }}"
                                       value="{{ old('phone') }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="fw-600 fs-14">{{ __('Contraseña') }}</label>
                                <input type="password" name="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       placeholder="{{ __('Crea una contraseña') }}" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="fw-600 fs-14">{{ __('Confirmar contraseña') }}</label>
                                <input type="password" name="password_confirmation"
                                       class="form-control"
                                       placeholder="{{ __('Repite tu contraseña') }}" required>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="agree" class="custom-control-input" id="agree" required>
                                    <label class="custom-control-label fs-14" for="agree">
                                        {{ __('Acepto los') }}
                                        <a href="{{ route('terms') }}" class="text-primary">{{ __('Términos y condiciones') }}</a>
                                        {{ __('y la') }}
                                        <a href="{{ route('privacy-policy') }}" class="text-primary">{{ __('Política de privacidad') }}</a>
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block fw-600">
                                {{ __('Crear cuenta') }}
                            </button>
                        </form>

                        <div class="text-center mt-4 fs-14">
                            {{ __('¿Ya tienes cuenta?') }}
                            <a href="{{ route('login') }}" class="text-primary fw-600">{{ __('Iniciar sesión') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
