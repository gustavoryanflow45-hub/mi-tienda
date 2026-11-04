{{-- Avisos flash del panel (success / warning / error). Copia propia de
     partials/flash para que el panel no dependa de las vistas de la tienda. --}}
@php
    $flashes = [
        'success' => ['class' => 'alert-success', 'icon' => 'la-check-circle'],
        'warning' => ['class' => 'alert-warning', 'icon' => 'la-exclamation-triangle'],
        'error' => ['class' => 'alert-danger', 'icon' => 'la-times-circle'],
    ];
@endphp

@foreach($flashes as $key => $flash)
    @if(session($key))
        <div class="container mt-3">
            <div class="alert {{ $flash['class'] }} alert-dismissible fade show" role="alert">
                <i class="las {{ $flash['icon'] }} mr-1"></i>
                {{ session($key) }}
                <button type="button" class="close" data-dismiss="alert" aria-label="{{ __('Cerrar') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    @endif
@endforeach
