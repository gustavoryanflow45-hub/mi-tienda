@extends('layouts.app')

@section('title', 'Dashboard')
@section('meta_description', 'Your verified seller dashboard')

@section('extra_css')
<style>
    body { background-color: #f2f3f8; }

    .wave-card { position: relative; overflow: hidden; border-radius: 0.75rem; padding: 1.5rem; color: white; height: 100%; box-shadow: 0 4px 20px rgba(0,0,0,0.12); transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .wave-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,0,0,0.18); }
    .wave-card::after { content: ""; position: absolute; bottom: 0; left: 0; width: 100%; height: 55%; background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.15)" d="M0,256L48,245.3C96,235,192,213,288,202.7C384,192,480,192,576,197.3C672,203,768,213,864,218.7C960,224,1056,224,1152,208C1248,192,1344,160,1392,144L1440,128L1440,320L0,320Z"></path></svg>'); background-size: cover; pointer-events: none; }
    .wave-card .stat-icon { position: absolute; top: 1rem; right: 1.25rem; font-size: 2.5rem; opacity: 0.25; }

    .dashboard-sidebar .profile-header { background: linear-gradient(135deg, #679941, #4e7a2e); border-radius: 0.75rem 0.75rem 0 0; }
    .verified-badge { background: linear-gradient(135deg, #43e97b, #38f9d7); color: #fff; border-radius: 20px; padding: 3px 12px; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 4px; }
    .aiz-side-nav-link { border-radius: 8px; transition: background 0.15s ease, color 0.15s ease; font-size: 14px; color: #555 !important; }
    .aiz-side-nav-link.bg-soft-primary { background-color: rgba(103,153,65,0.12) !important; color: #679941 !important; font-weight: 600; }
    .aiz-side-nav-link:hover { background-color: #f0f1f3 !important; color: #333 !important; opacity: 1 !important; }

    .avatar-placeholder { width: 80px; height: 80px; border-radius: 50%; background: rgba(255,255,255,0.25); border: 3px solid rgba(255,255,255,0.5); display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #fff; margin: 0 auto; overflow: hidden; }
    .avatar-placeholder img { width: 100%; height: 100%; object-fit: cover; }

    .info-card { background: #fff; border-radius: 0.75rem; box-shadow: 0 2px 12px rgba(0,0,0,0.06); transition: box-shadow 0.2s ease; }
    .info-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.1); }

    .btn-become-seller { background-color: #e8f5e0; color: #679941; border: 1px solid #c5e0b4; font-weight: 600; border-radius: 8px; transition: background 0.2s; }
    .btn-become-seller:hover { background-color: #d4edca; color: #4e7a2e; }

    .logout-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 9999; align-items: center; justify-content: center; }
    .logout-modal-overlay.show { display: flex; }
    .logout-modal-box { background: #fff; border-radius: 16px; padding: 2rem 1.75rem; width: 90%; max-width: 360px; text-align: center; box-shadow: 0 8px 32px rgba(0,0,0,0.18); animation: modalIn 0.25s ease; }
    @keyframes modalIn { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .logout-icon { width: 64px; height: 64px; background: linear-gradient(135deg, #f64f59, #c471ed); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
    .logout-icon i { font-size: 2rem; color: #fff; }
    .btn-logout-confirm { display: block; width: 100%; padding: 0.65rem; background: linear-gradient(135deg, #679941, #4e7a2e); color: #fff; border: none; border-radius: 8px; font-weight: 600; font-size: 1rem; cursor: pointer; margin-bottom: 0.75rem; }
    .btn-logout-confirm:hover { opacity: 0.9; }
    .btn-logout-cancel { display: block; width: 100%; padding: 0.65rem; background: #f1f3f5; color: #444; border: none; border-radius: 8px; font-weight: 600; font-size: 1rem; cursor: pointer; }
    .btn-logout-cancel:hover { background: #e2e6ea; }
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
                        <span class="verified-badge">
                            <i class="las la-check-circle"></i> Verified
                        </span>
                    </div>
                    <div class="bg-white shadow-sm rounded-bottom p-3">
                        <ul class="aiz-side-nav-list list-unstyled mb-0">
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/dashboard') }}" class="aiz-side-nav-link bg-soft-primary d-flex align-items-center text-reset p-2">
                                    <i class="las la-home mr-2 fs-16"></i><span>Dashboard</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/orders') }}" class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-file-invoice mr-2 fs-16"></i><span>Historial de Compras</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="#" class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-reply mr-2 fs-16"></i><span>Solicitud de Reembolso Enviada</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/wishlist') }}" class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-heart mr-2 fs-16"></i><span>Lista de Deseos</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ route('seller.products.index') }}" class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-box mr-2 fs-16"></i><span>Productos</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="#" class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-boxes mr-2 fs-16"></i><span>Wholesale Products</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="#" class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-ticket-alt mr-2 fs-16"></i><span>Cupos</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="#" class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-tags mr-2 fs-16"></i><span>Productos Clasificados</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ route('seller.orders.index') }}" class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-shopping-cart mr-2 fs-16"></i><span>Pedidos</span>
                                    @if(Auth::user()->unreadNotifications->count() > 0)
                                        <span style="margin-left:auto;background:#e74c3c;color:#fff;border-radius:12px;padding:1px 7px;font-size:11px;font-weight:700;">{{ Auth::user()->unreadNotifications->count() }}</span>
                                    @endif
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="#" class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-truck mr-2 fs-16"></i><span>Dispatch</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="#" class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-undo mr-2 fs-16"></i><span>Solicitud de Reembolso Recibida</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="#" class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-star mr-2 fs-16"></i><span>Reseña de Productos</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ route('wallet.index') }}" class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-wallet mr-2 fs-16"></i><span>Mi Billetera</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="#" class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-headset mr-2 fs-16"></i><span>Ticket de Soporte</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/profile') }}" class="aiz-side-nav-link d-flex align-items-center text-reset p-2">
                                    <i class="las la-user-cog mr-2 fs-16"></i><span>Administrar Perfil</span>
                                </a>
                            </li>
                        </ul>
                        <div class="mt-3 pt-3 border-top">
                            <a href="{{ url('/shops/create') }}" class="btn btn-block btn-become-seller">
                                <i class="las la-store mr-1"></i> Ser Vendedor
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CONTENIDO --}}
            <div class="col-lg-9">
                <h3 class="h4 fw-700 mb-4">Dashboard, {{ Auth::user()->name }}</h3>

                <div class="row gutters-10 mb-3">
                    <div class="col-6 col-md-3 mb-3">
                        <div class="wave-card" style="background: linear-gradient(135deg, #6a85b6, #bac8e0);">
                            <i class="las la-box stat-icon"></i>
                            <h2 class="fw-700 fs-32 mb-1 position-relative">{{ $stats['products'] ?? 0 }}</h2>
                            <p class="mb-0 opacity-80 fs-14 position-relative">Products</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <div class="wave-card" style="background: linear-gradient(135deg, #7b4397, #dc2430);">
                            <i class="las la-dollar-sign stat-icon"></i>
                            <h2 class="fw-700 fs-32 mb-1 position-relative">${{ number_format($stats['total_sale'] ?? 0, 2) }}</h2>
                            <p class="mb-0 opacity-80 fs-14 position-relative">Total Sale</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <div class="wave-card" style="background: linear-gradient(135deg, #56317a, #6e48aa);">
                            <i class="las la-chart-line stat-icon"></i>
                            <h2 class="fw-700 fs-32 mb-1 position-relative">${{ number_format($stats['total_profits'] ?? 0, 2) }}</h2>
                            <p class="mb-0 opacity-80 fs-14 position-relative">Total Profits</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <div class="wave-card" style="background: linear-gradient(135deg, #43e97b, #38f9d7);">
                            <i class="las la-check-circle stat-icon"></i>
                            <h2 class="fw-700 fs-32 mb-1 position-relative">{{ $stats['success_orders'] ?? 0 }}</h2>
                            <p class="mb-0 opacity-80 fs-14 position-relative">Success Orders</p>
                        </div>
                    </div>
                </div>

                <div class="row gutters-10 mb-4">
                    <div class="col-6 col-md-3 mb-3">
                        <div class="wave-card" style="background: linear-gradient(135deg, #c471ed, #f64f59);">
                            <i class="las la-eye stat-icon"></i>
                            <h2 class="fw-700 fs-32 mb-1 position-relative">{{ $stats['visitors'] ?? 0 }}</h2>
                            <p class="mb-0 opacity-80 fs-14 position-relative">Today Visitors</p>
                        </div>
                    </div>
                </div>

                <div class="row gutters-10">
                    <div class="col-md-7 mb-3">
                        <div class="info-card p-4 h-100">
                            <h4 class="h6 fw-600 mb-3 opacity-60"><i class="las la-map-marker mr-1 text-primary"></i> Default Delivery Address</h4>
                            @if(isset($address) && $address)
                                <p class="fs-14 mb-1 fw-600">{{ $address->full_name }}</p>
                                <p class="fs-13 opacity-70 mb-1">{{ $address->address }}</p>
                                <p class="fs-13 opacity-70 mb-1">{{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}</p>
                                <p class="fs-13 opacity-70 mb-0">{{ $address->phone }}</p>
                            @else
                                <div class="opacity-50 fs-13 mt-4 text-center py-3">
                                    <i class="las la-map-marker la-2x mb-2 d-block text-primary"></i>
                                    No address saved yet.
                                    <div class="mt-3"><a href="{{ url('/profile') }}" class="btn btn-sm btn-outline-primary">Add Address</a></div>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-5 mb-3">
                        <div class="info-card p-4 h-100 text-center d-flex flex-column justify-content-center align-items-center">
                            <i class="las la-box-open la-3x text-primary mb-3 opacity-50"></i>
                            <h4 class="h6 fw-600 mb-2 opacity-60">Purchased Package</h4>
                            <h5 class="fw-600 text-primary mb-4">{{ $package->name ?? 'Package not found' }}</h5>
                            <a href="{{ url('/packages') }}" class="btn btn-primary fw-600 px-4">Upgrade Package</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<div class="logout-modal-overlay" id="logoutModal">
    <div class="logout-modal-box">
        <div class="logout-icon"><i class="las la-sign-out-alt"></i></div>
        <h5 class="fw-700 mb-1">Sign out?</h5>
        <p class="opacity-60 fs-14 mb-4">Are you sure you want to log out of your account?</p>
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
document.getElementById('logoutModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('show');
});
</script>
@endsection