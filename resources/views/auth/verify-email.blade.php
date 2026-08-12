{{-- resources/views/auth/verify-email.blade.php --}}
@extends('layouts.app')

@section('title', 'Verifica tu correo')

@section('content')
<section class="py-5 my-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 text-center">
                        <i class="las la-envelope-open-text la-4x text-primary opacity-75 mb-3"></i>
                        <h4 class="fw-700 mb-2">Verifica tu correo electrónico</h4>

                        <p class="text-muted fs-14 mb-4">
                            Te enviamos un enlace de verificación a
                            <strong>{{ Auth::user()->email }}</strong>.
                            Ábrelo para activar tu cuenta.
                            El enlace caduca en {{ \App\Notifications\VerifyEmailNotification::EXPIRES_MINUTES }} minutos.
                        </p>

                        <p class="text-muted fs-13 mb-4">
                            ¿No te llegó? Revisa la carpeta de spam o pide uno nuevo.
                        </p>

                        <form method="POST" action="{{ route('verification.resend') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary fw-600 px-4">
                                <i class="las la-redo-alt mr-1"></i> Reenviar enlace
                            </button>
                        </form>

                        <a href="{{ route('dashboard') }}" class="d-inline-block mt-3 fs-14 text-muted">
                            Volver al panel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
