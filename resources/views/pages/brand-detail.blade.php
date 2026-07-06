@extends('layouts.app')

@section('title', $brand->name)

@section('content')
<div style="background:#f2f3f8; padding:30px 0 60px; min-height:60vh;">
    <div class="container">

        <div style="display:flex; align-items:center; gap:16px; margin-bottom:24px;">
            @if($brand->logo)
                <img src="{{ asset('storage/' . $brand->logo) }}" alt="{{ $brand->name }}"
                     style="max-height:50px; max-width:100px; object-fit:contain;">
            @endif
            <div>
                <h1 style="font-size:1.2rem; font-weight:700; color:#222; margin:0;">{{ $brand->name }}</h1>
                <p style="font-size:.83rem; color:#888; margin:0;">{{ $products->total() }} productos</p>
            </div>
        </div>

        @if($products->isEmpty())
            <div style="text-align:center; padding:60px 20px; color:#bbb;">
                <i class="las la-box-open" style="font-size:3rem; display:block; margin-bottom:12px;"></i>
                <p>No hay productos para esta marca.</p>
                <a href="{{ route('products.index') }}" style="color:#679941; font-size:.85rem;">← Ver todos los productos</a>
            </div>
        @else
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:16px;">
                @foreach($products as $product)
                    @include('partials.product-card', ['product' => $product])
                @endforeach
            </div>

            <div style="margin-top:24px;">
                {{ $products->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
