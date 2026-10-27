{{-- resources/views/admin/two-factor/challenge.blade.php --}}
@extends('layouts.app')

@section('title', __('Verificación en dos pasos'))

@section('content')
<section class="py-5 my-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <i class="las la-shield-alt" style="font-size:3rem;color:#679941;"></i>
                            <h4 class="fw-700">{{ __('Verificación en dos pasos') }}</h4>
                        </div>

                        @if($enrolled)
                            <p class="text-muted fs-14">{{ __('Escribe el código de 6 dígitos que muestra tu aplicación de autenticación.') }}</p>

                            <form method="POST" action="{{ route('admin.two-factor.verify') }}">
                                @csrf
                                <div class="form-group">
                                    <label class="fw-600 fs-14" for="tf-code">{{ __('Código') }}</label>
                                    <input type="text" name="code" id="tf-code" inputmode="numeric" pattern="[0-9 ]*"
                                           maxlength="7" autocomplete="one-time-code" autofocus
                                           class="form-control form-control-lg text-center @error('code') is-invalid @enderror">
                                    @error('code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-primary btn-block fw-600">{{ __('Verificar') }}</button>
                            </form>

                            <details class="mt-4">
                                <summary class="fs-14 text-primary" style="cursor:pointer;">{{ __('¿No tienes el móvil? Usa un código de recuperación') }}</summary>
                                <form method="POST" action="{{ route('admin.two-factor.verify') }}" class="mt-3">
                                    @csrf
                                    <div class="form-group">
                                        <label class="fw-600 fs-14" for="tf-recovery">{{ __('Código de recuperación') }}</label>
                                        <input type="text" name="recovery_code" id="tf-recovery" autocomplete="off"
                                               class="form-control text-center" placeholder="XXXXX-XXXXX">
                                    </div>
                                    <button type="submit" class="btn btn-outline-primary btn-block fw-600">{{ __('Usar código de recuperación') }}</button>
                                </form>
                            </details>
                        @else
                            <div class="alert alert-warning fs-14 mb-0">
                                <p class="mb-2">{{ __('Tu cuenta de administrador todavía no tiene el segundo factor configurado, y sin él no se puede entrar al panel.') }}</p>
                                <p class="mb-0">{{ __('Pide a quien administra el servidor un enlace de alta (php artisan admin:2fa-invite) y ábrelo con esta sesión iniciada.') }}</p>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.logout') }}" class="mt-4 text-center">
                            @csrf
                            <button type="submit" class="btn btn-link fs-14 text-muted">{{ __('Cerrar sesión del panel') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
