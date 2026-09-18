@extends('layouts.app')

@section('title', __('Agregar producto'))

@section('extra_css')
<style>
    body { background-color: #f2f3f8; }

    /* Sidebar */
    .dashboard-sidebar .profile-header { background: linear-gradient(135deg, #679941, #4e7a2e); border-radius: 0.75rem 0.75rem 0 0; }
    .verified-badge { background: linear-gradient(135deg, #43e97b, #38f9d7); color:#fff; border-radius:20px; padding:3px 12px; font-size:11px; font-weight:700; display:inline-flex; align-items:center; gap:4px; }
    .avatar-placeholder { width:80px; height:80px; border-radius:50%; background:rgba(255,255,255,.25); border:3px solid rgba(255,255,255,.5); display:flex; align-items:center; justify-content:center; font-size:2rem; color:#fff; margin:0 auto; overflow:hidden; }
    .avatar-placeholder img { width:100%; height:100%; object-fit:cover; }
    .aiz-side-nav-link { border-radius:8px; transition:background .15s,color .15s; font-size:14px; color:#555 !important; }
    .aiz-side-nav-link.active { background-color:rgba(103,153,65,.12)!important; color:#679941!important; font-weight:600; }
    .aiz-side-nav-link:hover { background-color:#f0f1f3!important; color:#333!important; }

    /* Form sections */
    .form-section { background:#fff; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,.07); margin-bottom:20px; overflow:hidden; }
    .form-section-header { padding:14px 20px; border-bottom:1px solid #eee; display:flex; align-items:center; gap:10px; }
    .form-section-header .section-icon { width:32px; height:32px; border-radius:8px; background:linear-gradient(135deg,#679941,#4e7a2e); display:flex; align-items:center; justify-content:center; }
    .form-section-header .section-icon i { color:#fff; font-size:14px; }
    .form-section-header h5 { margin:0; font-size:.9rem; font-weight:700; color:#333; }
    .form-section-body { padding:20px; }

    .form-label { font-size:.82rem; font-weight:600; color:#444; margin-bottom:5px; display:block; }
    .form-label .req { color:#e74c3c; margin-left:2px; }
    .form-control { border:1px solid #d8dde3; border-radius:6px; height:38px; font-size:.85rem; color:#333; background:#fafbfc; padding:6px 12px; width:100%; box-sizing:border-box; transition:border-color .2s,box-shadow .2s; }
    .form-control:focus { border-color:#679941; background:#fff; outline:none; box-shadow:0 0 0 3px rgba(103,153,65,.12); }
    textarea.form-control { height:auto; resize:vertical; }
    select.form-control { appearance:none; -webkit-appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23888' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 12px center; padding-right:32px; }

    /* Image upload */
    .img-upload-zone { border:2px dashed #d0d7de; border-radius:8px; padding:2rem 1rem; text-align:center; cursor:pointer; transition:border-color .2s,background .2s; background:#fafbfc; }
    .img-upload-zone:hover { border-color:#679941; background:#f5fbf1; }
    .img-upload-zone input[type=file] { display:none; }
    .img-upload-zone i { font-size:2rem; color:#bbb; display:block; margin-bottom:.5rem; }
    .img-upload-zone span { font-size:.83rem; color:#999; }
    .img-preview-row { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
    .img-preview-item { position:relative; width:72px; height:72px; border-radius:6px; overflow:hidden; border:1px solid #ddd; }
    .img-preview-item img { width:100%; height:100%; object-fit:cover; }

    /* Toggle */
    .toggle-row { display:flex; align-items:center; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f5f5f5; }
    .toggle-row:last-child { border-bottom:none; }
    .toggle-label { font-size:.85rem; color:#444; font-weight:500; }
    .toggle-switch { position:relative; width:42px; height:22px; flex-shrink:0; }
    .toggle-switch input { display:none; }
    .toggle-slider { position:absolute; inset:0; background:#ddd; border-radius:22px; cursor:pointer; transition:background .2s; }
    .toggle-slider::before { content:''; position:absolute; width:16px; height:16px; background:#fff; border-radius:50%; top:3px; left:3px; transition:left .2s; box-shadow:0 1px 4px rgba(0,0,0,.2); }
    .toggle-switch input:checked + .toggle-slider { background:#679941; }
    .toggle-switch input:checked + .toggle-slider::before { left:23px; }

    /* ── Variantes mejoradas ── */
    .variant-type-tabs { display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
    .variant-tab {
        padding:6px 16px; border-radius:20px; border:1px solid #ddd;
        background:#fff; font-size:.82rem; font-weight:600; color:#666;
        cursor:pointer; transition:all .2s; user-select:none;
    }
    .variant-tab:hover { border-color:#679941; color:#679941; }
    .variant-tab.active { background:#679941; border-color:#679941; color:#fff; }

    /* Quick size selector */
    .quick-sizes { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px; }
    .quick-size-btn {
        padding:4px 12px; border:1px solid #ddd; border-radius:4px;
        font-size:.78rem; font-weight:600; color:#555; background:#fff;
        cursor:pointer; transition:all .15s;
    }
    .quick-size-btn:hover { border-color:#679941; color:#679941; background:#f5fbf0; }

    /* Color picker row */
    .color-picker-row { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:12px; }
    .color-swatch-btn {
        width:28px; height:28px; border-radius:4px; border:2px solid transparent;
        cursor:pointer; transition:transform .15s, border-color .15s;
        position:relative;
    }
    .color-swatch-btn:hover { transform:scale(1.15); border-color:#333; }
    .color-swatch-btn[title="white"] { border:1px solid #ccc; }

    /* Stock rows */
    .stock-row { background:#f9fbf7; border:1px solid #e8f0e0; border-radius:8px; padding:12px; margin-bottom:10px; position:relative; }
    .stock-row-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
    .stock-row-badge { font-size:.75rem; font-weight:700; color:#679941; background:#e8f5e0; border-radius:12px; padding:2px 10px; }
    .stock-row-fields { display:grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap:10px; align-items:end; }
    @media(max-width:768px){ .stock-row-fields{ grid-template-columns:1fr 1fr; } }
    .btn-remove-stock { background:#fee; border:1px solid #fcc; color:#c00; border-radius:6px; padding:6px 10px; font-size:13px; cursor:pointer; height:38px; flex-shrink:0; }
    .btn-add-stock { background:#f0f8ea; border:1px solid #c5e0b4; color:#679941; border-radius:6px; padding:8px 16px; font-size:.83rem; font-weight:600; cursor:pointer; transition:background .2s; display:inline-flex; align-items:center; gap:6px; }
    .btn-add-stock:hover { background:#d4edca; }

    /* Info box variantes */
    .variant-info-box { background:#fffbea; border:1px solid #ffe58f; border-radius:8px; padding:12px 16px; margin-bottom:16px; font-size:.8rem; color:#7a6a00; }
    .variant-info-box strong { color:#5a4d00; display:block; margin-bottom:4px; }
    .variant-example { display:inline-block; background:#fff; border:1px solid #e0d070; border-radius:4px; padding:2px 8px; margin:2px; font-family:monospace; font-size:.78rem; }

    /* Submit */
    .btn-submit { background:linear-gradient(135deg,#679941,#4e7a2e); color:#fff; border:none; border-radius:8px; padding:12px 36px; font-size:.95rem; font-weight:700; cursor:pointer; transition:opacity .2s,transform .1s; }
    .btn-submit:hover { opacity:.9; transform:translateY(-1px); }

    /* Logout */
</style>
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row gutters-10">

            {{-- SIDEBAR --}}
            <div class="col-lg-3 mb-4">
                <div class="dashboard-sidebar">
                    <div class="profile-header p-4 text-center text-white">
                        <div class="avatar-placeholder mb-3">
                            <img src="{{ asset('assets/img/avatar-place.png') }}"
                                 onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=679941&color=fff&size=80'"
                                 alt="{{ Auth::user()->name }}">
                        </div>
                        <h4 class="h5 fw-600 fs-18 mb-1">{{ strtoupper(Auth::user()->name) }}</h4>
                        <p class="mb-2 text-truncate opacity-80 fs-13">{{ Auth::user()->email }}</p>
                        <span class="verified-badge"><i class="las la-check-circle"></i> {{ __('dashboard.verified') }}</span>
                    </div>
                    <div class="bg-white shadow-sm rounded-bottom p-3">
                        <ul class="aiz-side-nav-list list-unstyled mb-0">
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/dashboard') }}" class="aiz-side-nav-link d-flex align-items-center p-2">
                                    <i class="las la-home mr-2 fs-16"></i><span>{{ __('dashboard.nav.dashboard') }}</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/orders') }}" class="aiz-side-nav-link d-flex align-items-center p-2">
                                    <i class="las la-file-invoice mr-2 fs-16"></i><span>{{ __('dashboard.nav.purchase_history') }}</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/wishlist') }}" class="aiz-side-nav-link d-flex align-items-center p-2">
                                    <i class="las la-heart mr-2 fs-16"></i><span>{{ __('dashboard.nav.wishlist') }}</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/seller/products') }}" class="aiz-side-nav-link active d-flex align-items-center p-2">
                                    <i class="las la-box mr-2 fs-16"></i><span>{{ __('dashboard.nav.products') }}</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ route('seller.orders.index') }}" class="aiz-side-nav-link d-flex align-items-center p-2">
                                    <i class="las la-shopping-cart mr-2 fs-16"></i><span>{{ __('dashboard.nav.orders') }}</span>
                                    @php $newOrders = Auth::user()->newOrderNotificationsCount(); @endphp
                                    @if($newOrders > 0)
                                        <span style="margin-left:auto;background:#e74c3c;color:#fff;border-radius:12px;padding:1px 7px;font-size:11px;font-weight:700;">{{ $newOrders }}</span>
                                    @endif
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ route('wallet.index') }}" class="aiz-side-nav-link d-flex align-items-center p-2">
                                    <i class="las la-wallet mr-2 fs-16"></i><span>{{ __('dashboard.nav.wallet') }}</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/profile') }}" class="aiz-side-nav-link d-flex align-items-center p-2">
                                    <i class="las la-user-cog mr-2 fs-16"></i><span>{{ __('dashboard.nav.manage_profile') }}</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- FORMULARIO --}}
            <div class="col-lg-9">

                <div class="d-flex align-items-center mb-4">
                    <a href="{{ url('/seller/products') }}" class="btn btn-sm btn-outline-secondary mr-3">
                        <i class="las la-arrow-left mr-1"></i> Volver
                    </a>
                    <h3 class="h4 fw-700 mb-0">{{ __('Agregar producto') }}</h3>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger mb-4" style="border-radius:8px; font-size:.85rem;">
                        <ul class="mb-0 pl-3">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('seller.products.store') }}" method="POST" enctype="multipart/form-data" id="product-form">
                    @csrf

                    {{-- ① Información básica --}}
                    <div class="form-section">
                        <div class="form-section-header">
                            <div class="section-icon"><i class="las la-info-circle"></i></div>
                            <h5>{{ __('Información básica') }}</h5>
                        </div>
                        <div class="form-section-body">
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">{{ __('Nombre del producto') }} <span class="req">*</span></label>
                                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="{{ __('Ej: Camiseta Algodón Premium') }}" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('Unidad') }} <span class="req">*</span></label>
                                    <select name="unit" class="form-control" required>
                                        <option value="">{{ __('Seleccionar...') }}</option>
                                        <option value="pc"     {{ old('unit')=='pc'    ?'selected':'' }}>{{ __('Pieza (pc)') }}</option>
                                        <option value="par"    {{ old('unit')=='par'   ?'selected':'' }}>{{ __('Par') }}</option>
                                        <option value="kg"     {{ old('unit')=='kg'    ?'selected':'' }}>{{ __('Kilogramo (kg)') }}</option>
                                        <option value="litro"  {{ old('unit')=='litro' ?'selected':'' }}>{{ __('Litro') }}</option>
                                        <option value="metro"  {{ old('unit')=='metro' ?'selected':'' }}>{{ __('Metro') }}</option>
                                        <option value="caja"   {{ old('unit')=='caja'  ?'selected':'' }}>{{ __('Caja') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('Categoría') }} <span class="req">*</span></label>
                                    <select name="category_id" id="category_id" class="form-control" required onchange="onCategoryChange()">
                                        <option value="">{{ __('Seleccionar categoría...') }}</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}"
                                                    data-variant-type="{{ $cat->variant_type }}"
                                                    {{ old('category_id')==$cat->id?'selected':'' }}>
                                                {{ $cat->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('Marca') }}</label>
                                    <select name="brand_id" class="form-control">
                                        <option value="">{{ __('Sin marca') }}</option>
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}" {{ old('brand_id')==$brand->id?'selected':'' }}>{{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">{{ __('Descripción corta') }}</label>
                                    <textarea name="short_description" class="form-control" rows="2" placeholder="{{ __('Resumen breve del producto') }}">{{ old('short_description') }}</textarea>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">{{ __('Descripción completa') }}</label>
                                    <textarea name="description" class="form-control" rows="5" placeholder="{{ __('Describe tu producto en detalle...') }}">{{ old('description') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ② Imágenes --}}
                    <div class="form-section">
                        <div class="form-section-header">
                            <div class="section-icon"><i class="las la-image"></i></div>
                            <h5>{{ __('Imágenes del producto') }}</h5>
                        </div>
                        <div class="form-section-body">
                            <div class="row">
                                <div class="col-md-5 mb-3">
                                    <label class="form-label">{{ __('Imagen principal') }} <span class="req">*</span></label>
                                    <div class="img-upload-zone" onclick="document.getElementById('thumbnail').click()">
                                        <i class="las la-cloud-upload-alt"></i>
                                        <span>{{ __('Clic para subir la imagen principal') }}</span>
                                        <input type="file" id="thumbnail" name="thumbnail" accept="image/*"
                                               onchange="previewSingle(this,'thumb-preview')" required>
                                    </div>
                                    <div id="thumb-preview" class="img-preview-row mt-2"></div>
                                </div>
                                <div class="col-md-7 mb-3">
                                    <label class="form-label">{{ __('Imágenes adicionales') }} <span style="color:#aaa;font-weight:400;">({{ __('máx. 5 — aparecen como miniaturas laterales') }})</span></label>
                                    <div class="img-upload-zone" onclick="document.getElementById('photos').click()">
                                        <i class="las la-images"></i>
                                        <span>{{ __('Clic para subir imágenes adicionales') }}</span>
                                        <input type="file" id="photos" name="photos[]" accept="image/*" multiple
                                               onchange="previewMultiple(this,'photos-preview')">
                                    </div>
                                    <div id="photos-preview" class="img-preview-row mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ③ Precios --}}
                    <div class="form-section">
                        <div class="form-section-header">
                            <div class="section-icon"><i class="las la-dollar-sign"></i></div>
                            <h5>{{ __('Precios') }}</h5>
                        </div>
                        <div class="form-section-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('Precio de venta') }} <span class="req">*</span></label>
                                    <input type="number" name="unit_price" class="form-control" value="{{ old('unit_price') }}" placeholder="0.00" step="0.01" min="0" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('Precio de compra') }}</label>
                                    <input type="number" name="purchase_price" class="form-control" value="{{ old('purchase_price') }}" placeholder="0.00" step="0.01" min="0">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('Costo de envío') }}</label>
                                    <input type="number" name="shipping_cost" class="form-control" value="{{ old('shipping_cost', 0) }}" placeholder="0.00" step="0.01" min="0">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('Descuento') }}</label>
                                    <input type="number" name="discount" class="form-control" value="{{ old('discount', 0) }}" placeholder="0" min="0">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('Tipo de descuento') }}</label>
                                    <select name="discount_type" class="form-control">
                                        <option value="percent" {{ old('discount_type')=='percent'?'selected':'' }}>{{ __('Porcentaje (%)') }}</option>
                                        <option value="amount"  {{ old('discount_type')=='amount' ?'selected':'' }}>{{ __('Monto fijo ($)') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ④ Variantes y Stock --}}
                    <div class="form-section">
                        <div class="form-section-header">
                            <div class="section-icon"><i class="las la-layer-group"></i></div>
                            <h5>{{ __('Variantes y stock') }}</h5>
                        </div>
                        <div class="form-section-body">

                            {{-- Cantidades mínimas --}}
                            <div class="row mb-3">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('Cantidad mínima de compra') }}</label>
                                    <input type="number" name="min_qty" class="form-control" value="{{ old('min_qty', 1) }}" min="1">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('Alerta de stock bajo') }}</label>
                                    <input type="number" name="low_stock_qty" class="form-control" value="{{ old('low_stock_qty', 5) }}" min="0">
                                </div>
                            </div>

                            @include('partials.variant-builder')
                        </div>
                    </div>

                    {{-- ⑤ SEO --}}
                    <div class="form-section">
                        <div class="form-section-header">
                            <div class="section-icon"><i class="las la-search"></i></div>
                            <h5>{{ __('SEO (opcional)') }}</h5>
                        </div>
                        <div class="form-section-body">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label">{{ __('Meta título') }}</label>
                                    <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title') }}" placeholder="{{ __('Título para buscadores') }}">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">{{ __('Meta descripción') }}</label>
                                    <textarea name="meta_description" class="form-control" rows="2" placeholder="{{ __('Descripción para buscadores') }}">{{ old('meta_description') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ⑥ Opciones de publicación --}}
                    <div class="form-section">
                        <div class="form-section-header">
                            <div class="section-icon"><i class="las la-toggle-on"></i></div>
                            <h5>{{ __('Opciones de publicación') }}</h5>
                        </div>
                        <div class="form-section-body">
                            <div class="toggle-row">
                                <span class="toggle-label">{{ __('Publicar producto') }}</span>
                                <label class="toggle-switch">
                                    <input type="hidden" name="published" value="0">
                                    <input type="checkbox" name="published" value="1" {{ old('published', 1) ? 'checked' : '' }}>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="toggle-row">
                                <span class="toggle-label">
                                    {{ __('Destacado en Home') }}
                                    <small style="color:#aaa;font-weight:400;display:block;font-size:.75rem;">{{ __('Aparece en la sección de productos destacados del inicio') }}</small>
                                </span>
                                <label class="toggle-switch">
                                    <input type="hidden" name="featured" value="0">
                                    <input type="checkbox" name="featured" value="1" {{ old('featured') ? 'checked' : '' }}>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="toggle-row">
                                <span class="toggle-label">{{ __('Oferta del día') }}</span>
                                <label class="toggle-switch">
                                    <input type="hidden" name="todays_deal" value="0">
                                    <input type="checkbox" name="todays_deal" value="1" {{ old('todays_deal') ? 'checked' : '' }}>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="toggle-row">
                                <span class="toggle-label">{{ __('Producto digital') }}</span>
                                <label class="toggle-switch">
                                    <input type="hidden" name="digital" value="0">
                                    <input type="checkbox" name="digital" value="1" {{ old('digital') ? 'checked' : '' }}>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="toggle-row">
                                <span class="toggle-label">{{ __('Multiplicar precio por cantidad (envío)') }}</span>
                                <label class="toggle-switch">
                                    <input type="hidden" name="is_quantity_multiplied" value="0">
                                    <input type="checkbox" name="is_quantity_multiplied" value="1" {{ old('is_quantity_multiplied') ? 'checked' : '' }}>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="text-right mb-5">
                        <a href="{{ url('/seller/products') }}" class="btn btn-outline-secondary mr-2 px-4">{{ __('Cancelar') }}</a>
                        <button type="submit" class="btn-submit">
                            <i class="las la-save mr-1"></i> {{ __('Guardar producto') }}
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</section>

@endsection

@section('extra_js')
<script>

// ── Preview imágenes ─────────────────────────────────────────────
function previewSingle(input, containerId) {
    const container = document.getElementById(containerId);
    container.innerHTML = '';
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            container.innerHTML = `<div class="img-preview-item"><img src="${e.target.result}"></div>`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewMultiple(input, containerId) {
    const container = document.getElementById(containerId);
    container.innerHTML = '';
    Array.from(input.files).slice(0, 5).forEach(file => {
        const reader = new FileReader();
        reader.onload = e => {
            const div = document.createElement('div');
            div.className = 'img-preview-item';
            div.innerHTML = `<img src="${e.target.result}">`;
            container.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}
</script>
@endsection