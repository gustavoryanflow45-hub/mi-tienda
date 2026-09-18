@extends('layouts.app')

@section('title', __('Carga masiva de productos'))

@section('extra_css')
<style>
    body { background-color: #f2f3f8; }

    .dashboard-sidebar .profile-header { background:linear-gradient(135deg,#679941,#4e7a2e); border-radius:.75rem .75rem 0 0; }
    .verified-badge { background:linear-gradient(135deg,#43e97b,#38f9d7); color:#fff; border-radius:20px; padding:3px 12px; font-size:11px; font-weight:700; display:inline-flex; align-items:center; gap:4px; }
    .avatar-placeholder { width:80px; height:80px; border-radius:50%; background:rgba(255,255,255,.25); border:3px solid rgba(255,255,255,.5); display:flex; align-items:center; justify-content:center; font-size:2rem; font-weight:700; color:#fff; margin:0 auto; overflow:hidden; }
    .avatar-placeholder img { width:100%; height:100%; object-fit:cover; }
    .aiz-side-nav-link { border-radius:8px; transition:background .15s,color .15s; font-size:14px; color:#555!important; }
    .aiz-side-nav-link.active { background-color:rgba(103,153,65,.12)!important; color:#679941!important; font-weight:600; }
    .aiz-side-nav-link:hover { background-color:#f0f1f3!important; color:#333!important; }

    /* ── Bulk panel ── */
    .bulk-panel { background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.08); overflow:hidden; }
    .bulk-panel-header { padding:18px 24px; border-bottom:1px solid #eee; display:flex; align-items:center; gap:12px; }
    .bulk-panel-header .ph-icon { width:38px; height:38px; border-radius:9px; background:linear-gradient(135deg,#4776e6,#8e54e9); display:flex; align-items:center; justify-content:center; }
    .bulk-panel-header .ph-icon i { color:#fff; font-size:16px; }
    .bulk-panel-header h5 { margin:0; font-size:.95rem; font-weight:700; color:#333; }
    .bulk-panel-body { padding:28px 24px; }

    /* ── Upload zone ── */
    .csv-upload-zone {
        border:2px dashed #c8d8e4; border-radius:10px;
        padding:3rem 2rem; text-align:center; cursor:pointer;
        background:#f8fafb; transition:border-color .2s, background .2s;
    }
    .csv-upload-zone:hover, .csv-upload-zone.dragover { border-color:#679941; background:#f3faed; }
    .csv-upload-zone input[type=file] { display:none; }
    .csv-upload-zone .upload-icon { font-size:3rem; color:#b0c4c8; margin-bottom:1rem; display:block; }
    .csv-upload-zone h6 { font-weight:700; color:#333; margin-bottom:.4rem; }
    .csv-upload-zone p { font-size:.83rem; color:#999; margin:0; }
    .csv-upload-zone .file-name { font-size:.85rem; color:#679941; font-weight:600; margin-top:.75rem; display:none; }

    /* ── Steps ── */
    .steps { display:flex; gap:0; counter-reset:step; margin-bottom:2rem; }
    .step { flex:1; text-align:center; position:relative; }
    .step::before { counter-increment:step; content:counter(step); width:32px; height:32px; border-radius:50%; background:#e0e7ef; color:#888; font-weight:700; font-size:13px; display:flex; align-items:center; justify-content:center; margin:0 auto .5rem; position:relative; z-index:1; }
    .step.done::before { background:#679941; color:#fff; }
    .step::after { content:''; position:absolute; top:16px; left:calc(50% + 16px); width:calc(100% - 32px); height:2px; background:#e0e7ef; z-index:0; }
    .step:last-child::after { display:none; }
    .step.done::after { background:#679941; }
    .step-label { font-size:.78rem; color:#888; font-weight:500; }
    .step.done .step-label { color:#679941; }

    /* ── Template box ── */
    .template-box { background:#f0f7ff; border:1px solid #cce0ff; border-radius:8px; padding:16px 20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
    .template-box p { margin:0; font-size:.85rem; color:#334; }
    .btn-download { background:#fff; border:1px solid #aac8f0; color:#1a73e8; border-radius:6px; padding:7px 16px; font-size:.83rem; font-weight:600; cursor:pointer; text-decoration:none; transition:background .2s; display:inline-flex; align-items:center; gap:6px; }
    .btn-download:hover { background:#e8f0fe; color:#1a73e8; text-decoration:none; }

    /* ── Column mapping ── */
    .col-map-table { width:100%; border-collapse:collapse; font-size:.83rem; }
    .col-map-table th { background:#f8f9fa; padding:8px 12px; font-weight:700; color:#555; border-bottom:2px solid #eee; text-align:left; }
    .col-map-table td { padding:8px 12px; border-bottom:1px solid #f0f0f0; vertical-align:middle; }
    .col-map-table tr:last-child td { border-bottom:none; }
    .col-badge { background:#e8f5e0; color:#4a7a2a; border-radius:4px; padding:2px 8px; font-size:11px; font-weight:700; font-family:monospace; }
    .col-badge.req { background:#fde8e8; color:#b00; }

    /* ── Buttons ── */
    .btn-submit { background:linear-gradient(135deg,#679941,#4e7a2e); color:#fff; border:none; border-radius:8px; padding:12px 36px; font-size:.95rem; font-weight:700; cursor:pointer; transition:opacity .2s; }
    .btn-submit:hover { opacity:.9; }

    /* ── Results table ── */
    .result-row-ok td { background:#f3fbf3; }
    .result-row-err td { background:#fff5f5; }

    /* Logout */
</style>
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row gutters-10">

            {{-- ── SIDEBAR ── --}}
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

            {{-- ── CONTENIDO ── --}}
            <div class="col-lg-9">

                <div class="d-flex align-items-center mb-4">
                    <a href="{{ url('/seller/products') }}" class="btn btn-sm btn-outline-secondary mr-3">
                        <i class="las la-arrow-left mr-1"></i> Volver
                    </a>
                    <h3 class="h4 fw-700 mb-0">{{ __('Carga masiva de productos (CSV)') }}</h3>
                </div>

                {{-- Mensajes --}}
                @if(session('bulk_success'))
                    <div class="alert alert-success mb-4" style="border-radius:8px; font-size:.85rem;">
                        <i class="las la-check-circle mr-1"></i> {{ session('bulk_success') }}
                    </div>
                @endif
                @if(session('bulk_errors'))
                    <div class="alert alert-warning mb-4" style="border-radius:8px; font-size:.85rem;">
                        <strong>{{ __('Algunas filas tuvieron errores:') }}</strong>
                        <ul class="mb-0 mt-2 pl-3">
                            @foreach(session('bulk_errors') as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Pasos --}}
                <div class="steps mb-4">
                    <div class="step done"><span class="step-label">{{ __('Descargar plantilla') }}</span></div>
                    <div class="step done"><span class="step-label">{{ __('Completar CSV') }}</span></div>
                    <div class="step done"><span class="step-label">{{ __('Subir archivo') }}</span></div>
                    <div class="step"><span class="step-label">{{ __('Resultado') }}</span></div>
                </div>

                {{-- Panel principal --}}
                <div class="bulk-panel mb-4">
                    <div class="bulk-panel-header">
                        <div class="ph-icon"><i class="las la-file-csv"></i></div>
                        <h5>{{ __('Subir archivo CSV') }}</h5>
                    </div>
                    <div class="bulk-panel-body">

                        {{-- Descargar plantilla --}}
                        <div class="template-box mb-4">
                            <div>
                                <p class="fw-600 mb-1" style="color:#333;">📄 {{ __('Plantilla CSV de ejemplo') }}</p>
                                <p>{{ __('Descarga la plantilla, completa los datos y súbela aquí.') }}</p>
                            </div>
                            <a href="{{ route('seller.products.bulk.template') }}" class="btn-download">
                                <i class="las la-download"></i> {{ __('Descargar plantilla') }}
                            </a>
                        </div>

                        {{-- Zona de subida --}}
                        <form action="{{ route('seller.products.bulk.store') }}" method="POST" enctype="multipart/form-data" id="bulk-form">
                            @csrf
                            <div class="csv-upload-zone mb-3" id="drop-zone" onclick="document.getElementById('csv-file').click()">
                                <i class="las la-file-upload upload-icon"></i>
                                <h6>{{ __('Arrastra tu archivo CSV aquí') }}</h6>
                                <p>{{ __('o haz clic para seleccionarlo desde tu computadora') }}</p>
                                <p class="mt-2" style="font-size:.78rem; color:#bbb;">{{ __('Solo archivos .csv — máximo 5 MB') }}</p>
                                <div class="file-name" id="csv-file-name">
                                    <i class="las la-check-circle mr-1"></i> <span></span>
                                </div>
                                <input type="file" id="csv-file" name="csv_file" accept=".csv" required
                                       onchange="showFileName(this)">
                            </div>

                            <div class="text-right">
                                <a href="{{ url('/seller/products') }}" class="btn btn-outline-secondary mr-2 px-4">{{ __('Cancelar') }}</a>
                                <button type="submit" class="btn-submit">
                                    <i class="las la-upload mr-1"></i> {{ __('Procesar CSV') }}
                                </button>
                            </div>
                        </form>

                    </div>
                </div>

                {{-- Referencia de columnas --}}
                <div class="bulk-panel">
                    <div class="bulk-panel-header">
                        <div class="ph-icon" style="background:linear-gradient(135deg,#679941,#4e7a2e);">
                            <i class="las la-table"></i>
                        </div>
                        <h5>{{ __('Columnas del CSV') }}</h5>
                    </div>
                    <div class="bulk-panel-body p-0">
                        <div class="table-responsive">
                            <table class="col-map-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Columna CSV') }}</th>
                                        <th>{{ __('Campo') }}</th>
                                        <th>{{ __('Obligatorio') }}</th>
                                        <th>{{ __('Ejemplo') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><span class="col-badge req">name</span></td><td>{{ __('Nombre del producto') }}</td><td>✅ {{ __('Sí') }}</td><td>Camiseta Azul</td></tr>
                                    <tr><td><span class="col-badge req">category_id</span></td><td>{{ __('ID de categoría') }}</td><td>✅ {{ __('Sí') }}</td><td>3</td></tr>
                                    <tr><td><span class="col-badge req">unit_price</span></td><td>{{ __('Precio de venta') }}</td><td>✅ {{ __('Sí') }}</td><td>29.99</td></tr>
                                    <tr><td><span class="col-badge req">stock_qty</span></td><td>{{ __('Cantidad en stock') }}</td><td>✅ {{ __('Sí') }}</td><td>50</td></tr>
                                    <tr><td><span class="col-badge req">stock_price</span></td><td>{{ __('Precio de stock/variante') }}</td><td>✅ {{ __('Sí') }}</td><td>29.99</td></tr>
                                    <tr><td><span class="col-badge">brand_id</span></td><td>{{ __('ID de marca') }}</td><td>{{ __('No') }}</td><td>2</td></tr>
                                    <tr><td><span class="col-badge">purchase_price</span></td><td>{{ __('Precio de compra') }}</td><td>{{ __('No') }}</td><td>15.00</td></tr>
                                    <tr><td><span class="col-badge">discount</span></td><td>{{ __('Descuento (%)') }}</td><td>{{ __('No') }}</td><td>10</td></tr>
                                    <tr><td><span class="col-badge">discount_type</span></td><td>{{ __('Tipo de descuento') }}</td><td>{{ __('No') }}</td><td>percent / amount</td></tr>
                                    <tr><td><span class="col-badge">unit</span></td><td>{{ __('Unidad de medida') }}</td><td>{{ __('No') }}</td><td>pieza</td></tr>
                                    <tr><td><span class="col-badge">shipping_cost</span></td><td>{{ __('Costo de envío') }}</td><td>{{ __('No') }}</td><td>5.00</td></tr>
                                    <tr><td><span class="col-badge">short_description</span></td><td>{{ __('Descripción corta') }}</td><td>{{ __('No') }}</td><td>Algodón 100%</td></tr>
                                    <tr><td><span class="col-badge">description</span></td><td>{{ __('Descripción completa') }}</td><td>{{ __('No') }}</td><td>{{ __('Descripción larga...') }}</td></tr>
                                    <tr><td><span class="col-badge">sku</span></td><td>{{ __('SKU del stock') }}</td><td>{{ __('No') }}</td><td>CAM-AZU-001</td></tr>
                                    <tr><td><span class="col-badge">variant</span></td><td>{{ __('Variante (color, talla...)') }}</td><td>{{ __('No') }}</td><td>Azul-L</td></tr>
                                    <tr><td><span class="col-badge">published</span></td><td>{{ __('Publicado (1/0)') }}</td><td>{{ __('No') }}</td><td>1</td></tr>
                                    <tr><td><span class="col-badge">featured</span></td><td>{{ __('Destacado (1/0)') }}</td><td>{{ __('No') }}</td><td>0</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

@endsection

@section('extra_js')
<script>
function showFileName(input) {
    if (input.files && input.files[0]) {
        const nameEl = document.getElementById('csv-file-name');
        nameEl.querySelector('span').textContent = input.files[0].name;
        nameEl.style.display = 'block';
    }
}

// Drag & drop
const zone = document.getElementById('drop-zone');
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file && file.name.endsWith('.csv')) {
        document.getElementById('csv-file').files = e.dataTransfer.files;
        showFileName(document.getElementById('csv-file'));
    }
});
</script>
@endsection