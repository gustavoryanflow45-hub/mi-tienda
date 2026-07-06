@extends('layouts.app')

@section('title', 'Carga Masiva de Productos')

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
    .logout-modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9999; align-items:center; justify-content:center; }
    .logout-modal-overlay.show { display:flex; }
    .logout-modal-box { background:#fff; border-radius:16px; padding:2rem 1.75rem; width:90%; max-width:360px; text-align:center; box-shadow:0 8px 32px rgba(0,0,0,.18); }
    .logout-icon { width:64px; height:64px; background:linear-gradient(135deg,#f64f59,#c471ed); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; }
    .logout-icon i { font-size:2rem; color:#fff; }
    .btn-logout-confirm { display:block; width:100%; padding:.65rem; background:linear-gradient(135deg,#679941,#4e7a2e); color:#fff; border:none; border-radius:8px; font-weight:600; font-size:1rem; cursor:pointer; margin-bottom:.75rem; }
    .btn-logout-cancel { display:block; width:100%; padding:.65rem; background:#f1f3f5; color:#444; border:none; border-radius:8px; font-weight:600; font-size:1rem; cursor:pointer; }
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
                        <span class="verified-badge"><i class="las la-check-circle"></i> Verified</span>
                    </div>
                    <div class="bg-white shadow-sm rounded-bottom p-3">
                        <ul class="aiz-side-nav-list list-unstyled mb-0">
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/dashboard') }}" class="aiz-side-nav-link d-flex align-items-center p-2">
                                    <i class="las la-home mr-2 fs-16"></i><span>Dashboard</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/seller/products') }}" class="aiz-side-nav-link active d-flex align-items-center p-2">
                                    <i class="las la-box mr-2 fs-16"></i><span>Products</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ route('seller.orders.index') }}" class="aiz-side-nav-link d-flex align-items-center p-2">
                                    <i class="las la-shopping-cart mr-2 fs-16"></i><span>Pedidos</span>
                                    @if(Auth::user()->unreadNotifications->count() > 0)
                                        <span style="margin-left:auto;background:#e74c3c;color:#fff;border-radius:12px;padding:1px 7px;font-size:11px;font-weight:700;">{{ Auth::user()->unreadNotifications->count() }}</span>
                                    @endif
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ route('wallet.index') }}" class="aiz-side-nav-link d-flex align-items-center p-2">
                                    <i class="las la-wallet mr-2 fs-16"></i><span>Mi Billetera</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/profile') }}" class="aiz-side-nav-link d-flex align-items-center p-2">
                                    <i class="las la-user-cog mr-2 fs-16"></i><span>Administrar Perfil</span>
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
                    <h3 class="h4 fw-700 mb-0">Carga Masiva de Productos (CSV)</h3>
                </div>

                {{-- Mensajes --}}
                @if(session('bulk_success'))
                    <div class="alert alert-success mb-4" style="border-radius:8px; font-size:.85rem;">
                        <i class="las la-check-circle mr-1"></i> {{ session('bulk_success') }}
                    </div>
                @endif
                @if(session('bulk_errors'))
                    <div class="alert alert-warning mb-4" style="border-radius:8px; font-size:.85rem;">
                        <strong>Algunas filas tuvieron errores:</strong>
                        <ul class="mb-0 mt-2 pl-3">
                            @foreach(session('bulk_errors') as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Pasos --}}
                <div class="steps mb-4">
                    <div class="step done"><span class="step-label">Descargar plantilla</span></div>
                    <div class="step done"><span class="step-label">Completar CSV</span></div>
                    <div class="step done"><span class="step-label">Subir archivo</span></div>
                    <div class="step"><span class="step-label">Resultado</span></div>
                </div>

                {{-- Panel principal --}}
                <div class="bulk-panel mb-4">
                    <div class="bulk-panel-header">
                        <div class="ph-icon"><i class="las la-file-csv"></i></div>
                        <h5>Subir archivo CSV</h5>
                    </div>
                    <div class="bulk-panel-body">

                        {{-- Descargar plantilla --}}
                        <div class="template-box mb-4">
                            <div>
                                <p class="fw-600 mb-1" style="color:#333;">📄 Plantilla CSV de ejemplo</p>
                                <p>Descarga la plantilla, completa los datos y súbela aquí.</p>
                            </div>
                            <a href="{{ route('seller.products.bulk.template') }}" class="btn-download">
                                <i class="las la-download"></i> Descargar plantilla
                            </a>
                        </div>

                        {{-- Zona de subida --}}
                        <form action="{{ route('seller.products.bulk.store') }}" method="POST" enctype="multipart/form-data" id="bulk-form">
                            @csrf
                            <div class="csv-upload-zone mb-3" id="drop-zone" onclick="document.getElementById('csv-file').click()">
                                <i class="las la-file-upload upload-icon"></i>
                                <h6>Arrastra tu archivo CSV aquí</h6>
                                <p>o haz clic para seleccionar desde tu computadora</p>
                                <p class="mt-2" style="font-size:.78rem; color:#bbb;">Solo archivos .csv — máximo 5 MB</p>
                                <div class="file-name" id="csv-file-name">
                                    <i class="las la-check-circle mr-1"></i> <span></span>
                                </div>
                                <input type="file" id="csv-file" name="csv_file" accept=".csv" required
                                       onchange="showFileName(this)">
                            </div>

                            <div class="text-right">
                                <a href="{{ url('/seller/products') }}" class="btn btn-outline-secondary mr-2 px-4">Cancelar</a>
                                <button type="submit" class="btn-submit">
                                    <i class="las la-upload mr-1"></i> Procesar CSV
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
                        <h5>Columnas requeridas en el CSV</h5>
                    </div>
                    <div class="bulk-panel-body p-0">
                        <div class="table-responsive">
                            <table class="col-map-table">
                                <thead>
                                    <tr>
                                        <th>Columna CSV</th>
                                        <th>Campo</th>
                                        <th>Obligatorio</th>
                                        <th>Ejemplo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><span class="col-badge req">name</span></td><td>Nombre del producto</td><td>✅ Sí</td><td>Camiseta Azul</td></tr>
                                    <tr><td><span class="col-badge req">category_id</span></td><td>ID de categoría</td><td>✅ Sí</td><td>3</td></tr>
                                    <tr><td><span class="col-badge req">unit_price</span></td><td>Precio de venta</td><td>✅ Sí</td><td>29.99</td></tr>
                                    <tr><td><span class="col-badge req">stock_qty</span></td><td>Cantidad en stock</td><td>✅ Sí</td><td>50</td></tr>
                                    <tr><td><span class="col-badge req">stock_price</span></td><td>Precio de stock/variante</td><td>✅ Sí</td><td>29.99</td></tr>
                                    <tr><td><span class="col-badge">brand_id</span></td><td>ID de marca</td><td>No</td><td>2</td></tr>
                                    <tr><td><span class="col-badge">purchase_price</span></td><td>Precio de compra</td><td>No</td><td>15.00</td></tr>
                                    <tr><td><span class="col-badge">discount</span></td><td>Descuento (%)</td><td>No</td><td>10</td></tr>
                                    <tr><td><span class="col-badge">discount_type</span></td><td>Tipo descuento</td><td>No</td><td>percent / amount</td></tr>
                                    <tr><td><span class="col-badge">unit</span></td><td>Unidad de medida</td><td>No</td><td>pieza</td></tr>
                                    <tr><td><span class="col-badge">shipping_cost</span></td><td>Costo de envío</td><td>No</td><td>5.00</td></tr>
                                    <tr><td><span class="col-badge">short_description</span></td><td>Descripción corta</td><td>No</td><td>Algodón 100%</td></tr>
                                    <tr><td><span class="col-badge">description</span></td><td>Descripción completa</td><td>No</td><td>Descripción larga...</td></tr>
                                    <tr><td><span class="col-badge">sku</span></td><td>SKU del stock</td><td>No</td><td>CAM-AZU-001</td></tr>
                                    <tr><td><span class="col-badge">variant</span></td><td>Variante (color, talla...)</td><td>No</td><td>Azul-L</td></tr>
                                    <tr><td><span class="col-badge">published</span></td><td>Publicado (1/0)</td><td>No</td><td>1</td></tr>
                                    <tr><td><span class="col-badge">featured</span></td><td>Destacado (1/0)</td><td>No</td><td>0</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

{{-- Logout modal --}}
<div class="logout-modal-overlay" id="logoutModal">
    <div class="logout-modal-box">
        <div class="logout-icon"><i class="las la-sign-out-alt"></i></div>
        <h5 class="fw-700 mb-1">Sign out?</h5>
        <p class="opacity-60 fs-14 mb-4">Are you sure you want to log out?</p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout-confirm"><i class="las la-sign-out-alt mr-1"></i> Yes, sign out</button>
        </form>
        <button class="btn-logout-cancel" onclick="document.getElementById('logoutModal').classList.remove('show')">Cancel</button>
    </div>
</div>
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

document.getElementById('logoutModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('show');
});
</script>
@endsection