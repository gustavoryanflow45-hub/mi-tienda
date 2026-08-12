{{-- Mensajes flash globales (success / warning / error) --}}
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
                <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    @endif
@endforeach
