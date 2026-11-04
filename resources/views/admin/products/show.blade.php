@extends('layouts.admin')

@section('title', $product->name)

@section('content')
<div class="container pb-4">

    <div class="admin-page-title">
        <h1>
            <a href="{{ route('admin.products.index') }}" class="text-muted mr-1" title="{{ __('Volver') }}"><i class="las la-arrow-left"></i></a>
            {{ $product->name }}
        </h1>
        <div class="d-flex flex-wrap" style="gap:.4rem;">
            @include('admin.products.partials.status', ['product' => $product])
            <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-sm btn-light"><i class="las la-pen"></i> {{ __('Editar') }}</a>
            @include('admin.products.partials.moderate', ['product' => $product])
            @if($product->published && $product->approved)
                <a href="{{ route('products.show', $product->slug) }}" target="_blank" rel="noopener" class="btn btn-sm btn-light">
                    <i class="las la-external-link-alt"></i> {{ __('Ver en la tienda') }}
                </a>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="admin-card">
                <div class="admin-card-body">
                    <img src="{{ uploaded_asset($product->thumbnail) }}" alt="{{ $product->name }}" class="w-100 rounded mb-2" style="aspect-ratio:1/1;object-fit:cover;">
                    @if(! empty($product->photos))
                        <div class="d-flex flex-wrap" style="gap:6px;">
                            @foreach($product->photos as $photo)
                                <img src="{{ uploaded_asset($photo) }}" alt="" class="thumb" style="width:56px;height:56px;object-fit:cover;border-radius:6px;" loading="lazy">
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="admin-card">
                <div class="admin-card-body">
                    <h2>{{ __('Datos') }}</h2>
                    <dl class="dl-admin">
                        <dt>{{ __('Tienda') }}</dt>
                        <dd>
                            {{ $product->seller?->shop?->name ?? '—' }}
                            @if($product->seller)
                                <span class="text-muted">· {{ $product->seller->name }} ({{ $product->seller->email }})</span>
                            @endif
                        </dd>
                        <dt>{{ __('Categoría') }}</dt>
                        <dd>{{ $product->category?->name ?? '—' }}</dd>
                        <dt>{{ __('Marca') }}</dt>
                        <dd>{{ $product->brand?->name ?? '—' }}</dd>
                        <dt>{{ __('Precio') }}</dt>
                        <dd>
                            ${{ number_format($product->unit_price, 2) }}
                            @if($product->discount > 0)
                                <span class="text-muted">· {{ __('descuento') }} {{ $product->discount }}{{ $product->discount_type === 'percent' ? '%' : ' $' }}</span>
                            @endif
                        </dd>
                        <dt>{{ __('Unidad') }}</dt>
                        <dd>{{ $product->unit }}</dd>
                        <dt>{{ __('Creado') }}</dt>
                        <dd>{{ $product->created_at?->format('d/m/Y H:i') }}</dd>
                        <dt>{{ __('Actualizado') }}</dt>
                        <dd>{{ $product->updated_at?->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-body">
                    <h2>{{ __('Combinaciones y stock') }}</h2>
                    <div class="table-responsive">
                        <table class="table table-admin">
                            <thead>
                                <tr>
                                    <th>{{ __('Variante') }}</th>
                                    <th>{{ __('Precio') }}</th>
                                    <th>{{ __('Stock') }}</th>
                                    <th>SKU</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($product->stocks as $stock)
                                    <tr>
                                        <td>@include('partials.variant-badge', ['parts' => $stock->variant_parts, 'small' => true, 'fallback' => '—'])</td>
                                        <td>${{ number_format($stock->price, 2) }}</td>
                                        <td>
                                            {{ $stock->qty }}
                                            @if($stock->qty <= 0)
                                                <span class="pill pill-warn">{{ __('Agotado') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-muted">{{ $stock->sku ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted">{{ __('Sin filas de stock.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($product->short_description || $product->description)
                <div class="admin-card">
                    <div class="admin-card-body">
                        <h2>{{ __('Descripción') }}</h2>
                        @if($product->short_description)
                            <p class="fs-14">{{ $product->short_description }}</p>
                        @endif
                        {{-- Texto del vendedor: se muestra escapado, sin HTML. --}}
                        <div class="fs-14 text-muted" style="white-space:pre-line;">{{ strip_tags((string) $product->description) }}</div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
