{{-- resources/views/errors/429.blade.php — límite de peticiones superado --}}
@extends('layouts.app')

@section('title', __('Demasiadas peticiones'))

@section('content')
<section class="py-5 my-5">
    <div class="container text-center">
        <i class="las la-hourglass-half" style="font-size:3.5rem;color:#679941;"></i>
        <h1 class="h3 fw-700 mt-3">{{ __('Demasiadas peticiones') }}</h1>
        <p class="text-muted">{{ $message ?? __('Espera un momento y vuelve a intentarlo.') }}</p>
        <a href="{{ url()->current() }}" class="btn btn-primary mt-2">{{ __('Reintentar') }}</a>
    </div>
</section>
@endsection
