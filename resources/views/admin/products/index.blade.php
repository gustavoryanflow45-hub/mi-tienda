@extends('layouts.admin')

@section('title', __('Moderación de productos'))

@section('content')
<div class="container pb-4">

    <div class="admin-page-title">
        <h1>{{ __('Moderación de productos') }}</h1>
        <span class="text-muted fs-13">
            {{ __(':total productos · :active a la venta · :disabled desactivados · :unpublished sin publicar', [
                'total' => $counts['total'],
                'active' => $counts['active'],
                'disabled' => $counts['disabled'],
                'unpublished' => $counts['unpublished'],
            ]) }}
        </span>
    </div>

    <div class="admin-card">
        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.products.index') }}" class="admin-filters">
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('Buscar por nombre') }}" aria-label="{{ __('Buscar por nombre') }}">
                <select name="seller" class="form-control" aria-label="{{ __('Tienda') }}">
                    <option value="">{{ __('Todas las tiendas') }}</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->user_id }}" @selected((string) request('seller') === (string) $shop->user_id)>{{ $shop->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-control" aria-label="{{ __('Estado') }}">
                    <option value="">{{ __('Todos los estados') }}</option>
                    <option value="active" @selected(request('status') === 'active')>{{ __('A la venta') }}</option>
                    <option value="disabled" @selected(request('status') === 'disabled')>{{ __('Desactivados por moderación') }}</option>
                    <option value="unpublished" @selected(request('status') === 'unpublished')>{{ __('Sin publicar por su vendedor') }}</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm px-3">{{ __('Filtrar') }}</button>
                @if(request()->hasAny(['search', 'seller', 'status']))
                    <a href="{{ route('admin.products.index') }}" class="btn btn-light btn-sm">{{ __('Limpiar') }}</a>
                @endif
            </form>
        </div>
    </div>

    <div class="admin-card">
        <div class="table-responsive">
            <table class="table table-admin">
                <thead>
                    <tr>
                        <th></th>
                        <th>{{ __('Producto') }}</th>
                        <th>{{ __('Tienda') }}</th>
                        <th>{{ __('Precio') }}</th>
                        <th>{{ __('Estado') }}</th>
                        <th class="text-right">{{ __('Acciones') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td><img src="{{ uploaded_asset($product->thumbnail) }}" alt="" class="thumb" loading="lazy"></td>
                            <td>
                                <a href="{{ route('admin.products.show', $product->id) }}" class="fw-600 text-reset">{{ $product->name }}</a>
                                <div class="text-muted fs-12">{{ $product->category?->name ?? '—' }} · {{ trans_choice(':count combinación|:count combinaciones', $product->stocks_count, ['count' => $product->stocks_count]) }}</div>
                            </td>
                            <td>{{ $product->seller?->shop?->name ?? $product->seller?->name ?? '—' }}</td>
                            <td>${{ number_format($product->unit_price, 2) }}</td>
                            <td>
                                @include('admin.products.partials.status', ['product' => $product])
                            </td>
                            <td class="text-right text-nowrap">
                                <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-light btn-sm" title="{{ __('Editar') }}"><i class="las la-pen"></i></a>
                                @include('admin.products.partials.moderate', ['product' => $product, 'compact' => true])
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No hay productos con esos filtros.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $products->links() }}
</div>
@endsection
