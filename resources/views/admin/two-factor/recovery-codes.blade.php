{{-- resources/views/admin/two-factor/recovery-codes.blade.php --}}
@extends('layouts.app')

@section('title', __('Códigos de recuperación'))

@section('content')
<section class="py-5 my-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h4 class="fw-700 text-center mb-3">{{ __('Verificación en dos pasos activada') }}</h4>

                        <div class="alert alert-warning fs-14">
                            {{ __('Guarda estos códigos en un lugar seguro. Cada uno sirve una sola vez para entrar si pierdes el móvil. No se volverán a mostrar.') }}
                        </div>

                        <ul class="list-unstyled row text-center mb-4">
                            @foreach($codes as $code)
                                <li class="col-6 mb-2"><code class="fs-15">{{ $code }}</code></li>
                            @endforeach
                        </ul>

                        <a href="{{ route('admin.home') }}" class="btn btn-primary btn-block fw-600">{{ __('Ya los guardé, ir al panel') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
