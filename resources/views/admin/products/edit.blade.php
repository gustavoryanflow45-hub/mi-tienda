@extends('layouts.admin')

@section('title', __('Editar producto'))

@section('content')
<div class="container pb-5">

    <div class="admin-page-title">
        <h1>
            <a href="{{ route('admin.products.show', $product->id) }}" class="text-muted mr-1" title="{{ __('Volver') }}"><i class="las la-arrow-left"></i></a>
            {{ __('Editar producto') }}
        </h1>
        <span class="text-muted fs-13">{{ __('Los cambios quedan en el registro de auditoría. El dueño del producto no cambia.') }}</span>
    </div>

    {{-- El mismo formulario que el panel del vendedor; lo guarda ProductService. --}}
    @include('partials.product-form', [
        'formAction' => route('admin.products.update', $product->id),
        'cancelUrl' => route('admin.products.show', $product->id),
    ])
</div>
@endsection
