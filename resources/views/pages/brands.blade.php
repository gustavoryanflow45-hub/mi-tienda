@extends('layouts.app')

@section('title', 'Marcas')

@section('content')
<div style="background:#f2f3f8; padding:30px 0 60px; min-height:60vh;">
    <div class="container">

        <h1 style="font-size:1.2rem; font-weight:700; color:#222; margin-bottom:24px;">
            <i class="las la-tags" style="color:#679941;"></i> Todas las Marcas
        </h1>

        @if($brands->isEmpty())
            <div style="text-align:center; padding:60px 20px; color:#bbb;">
                <i class="las la-tags" style="font-size:3rem; display:block; margin-bottom:12px;"></i>
                <p>No hay marcas disponibles.</p>
            </div>
        @else
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(160px, 1fr)); gap:16px;">
                @foreach($brands as $brand)
                    <a href="{{ route('brands.show', $brand->slug) }}"
                       style="background:#fff; border-radius:10px; box-shadow:0 1px 6px rgba(0,0,0,.07); padding:20px; text-align:center; text-decoration:none; transition:box-shadow .2s;"
                       onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.12)'"
                       onmouseout="this.style.boxShadow='0 1px 6px rgba(0,0,0,.07)'">
                        @if($brand->logo)
                            <img src="{{ asset('storage/' . $brand->logo) }}" alt="{{ $brand->name }}"
                                 style="max-height:60px; max-width:120px; object-fit:contain; margin-bottom:10px;">
                        @else
                            <div style="height:60px; display:flex; align-items:center; justify-content:center; margin-bottom:10px;">
                                <i class="las la-store" style="font-size:2rem; color:#ccc;"></i>
                            </div>
                        @endif
                        <span style="font-size:.85rem; font-weight:600; color:#333;">{{ $brand->name }}</span>
                    </a>
                @endforeach
            </div>
        @endif

    </div>
</div>
@endsection
