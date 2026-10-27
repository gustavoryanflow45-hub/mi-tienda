{{-- resources/views/admin/two-factor/setup.blade.php --}}
@extends('layouts.app')

@section('title', __('Configurar la verificación en dos pasos'))

@section('content')
<section class="py-5 my-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h4 class="fw-700 text-center mb-3">{{ __('Configurar la verificación en dos pasos') }}</h4>

                        <ol class="fs-14 pl-3">
                            <li>{{ __('Abre tu aplicación de autenticación (Google Authenticator, Authy, 1Password…).') }}</li>
                            <li>{{ __('Escanea este código QR o escribe la clave a mano.') }}</li>
                            <li>{{ __('Escribe el código de 6 dígitos que te muestre para confirmar.') }}</li>
                        </ol>

                        <div class="text-center my-3">
                            <div class="d-inline-block p-2 bg-white border rounded" style="max-width:100%;">{!! $qr !!}</div>
                        </div>

                        <p class="text-center fs-13 text-muted mb-1">{{ __('Clave para escribir a mano') }}</p>
                        <p class="text-center"><code class="fs-14" style="word-break:break-all;">{{ trim(chunk_split($secret, 4, ' ')) }}</code></p>

                        <form method="POST" action="{{ $action }}">
                            @csrf
                            <div class="form-group">
                                <label class="fw-600 fs-14" for="tf-setup-code">{{ __('Código') }}</label>
                                <input type="text" name="code" id="tf-setup-code" inputmode="numeric" pattern="[0-9 ]*"
                                       maxlength="7" autocomplete="one-time-code" required
                                       class="form-control form-control-lg text-center @error('code') is-invalid @enderror">
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary btn-block fw-600">{{ __('Activar') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
