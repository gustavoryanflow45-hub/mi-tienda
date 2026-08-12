@extends('layouts.app')

@section('title', __('dashboard.title'))
@section('meta_description', __('dashboard.meta_unverified'))

@section('extra_css')
<style>
    body { background-color: #f2f3f8; }

    .wave-card { position: relative; overflow: hidden; border-radius: 0.75rem; padding: 1.5rem; color: white; height: 100%; box-shadow: 0 4px 20px rgba(0,0,0,0.12); transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .wave-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,0,0,0.18); }
    .wave-card::after { content: ""; position: absolute; bottom: 0; left: 0; width: 100%; height: 55%; background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.15)" d="M0,256L48,245.3C96,235,192,213,288,202.7C384,192,480,192,576,197.3C672,203,768,213,864,218.7C960,224,1056,224,1152,208C1248,192,1344,160,1392,144L1440,128L1440,320L0,320Z"></path></svg>'); background-size: cover; pointer-events: none; }
    .wave-card .stat-icon { position: absolute; top: 1rem; right: 1.25rem; font-size: 2.5rem; opacity: 0.25; }

    .dashboard-sidebar .profile-header { background: linear-gradient(135deg, #679941, #4e7a2e); border-radius: 0.75rem 0.75rem 0 0; }
    .unverified-badge { background: linear-gradient(135deg, #f39c12, #f64f59); color: #fff; border-radius: 20px; padding: 3px 12px; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 4px; }
    .aiz-side-nav-link { border-radius: 8px; transition: background 0.15s ease, color 0.15s ease; font-size: 14px; color: #555 !important; }
    .aiz-side-nav-link.bg-soft-primary { background-color: rgba(103,153,65,0.12) !important; color: #679941 !important; font-weight: 600; }
    .aiz-side-nav-link:hover { background-color: rgba(103,153,65,0.08) !important; color: #679941 !important; opacity: 1 !important; }

    .avatar-placeholder { width: 80px; height: 80px; border-radius: 50%; background: rgba(255,255,255,0.25); border: 3px solid rgba(255,255,255,0.5); display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #fff; margin: 0 auto; overflow: hidden; }
    .avatar-placeholder img { width: 100%; height: 100%; object-fit: cover; }

    .verify-alert { background: linear-gradient(135deg, #fff8ec, #fff3cd); border-left: 4px solid #f39c12; border-radius: 10px; padding: 1.25rem 1.5rem; box-shadow: 0 2px 12px rgba(243,156,18,0.12); }
    .verify-alert .verify-icon { color: #f39c12; font-size: 1.75rem; flex-shrink: 0; }
    .verify-alert .verify-title { color: #b7770d; font-size: 15px; font-weight: 700; }
    .verify-alert .verify-text { color: #7a6435; font-size: 13px; line-height: 1.5; }

    .info-card { background: #fff; border-radius: 0.75rem; box-shadow: 0 2px 12px rgba(0,0,0,0.06); transition: box-shadow 0.2s ease; }
    .info-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.1); }

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
                        <span class="unverified-badge">
                            <i class="las la-exclamation-circle"></i> {{ __('dashboard.not_verified') }}
                        </span>
                    </div>
                    <div class="bg-white shadow-sm rounded-bottom p-3">
                        <ul class="aiz-side-nav-list list-unstyled mb-0">
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/dashboard') }}" class="aiz-side-nav-link bg-soft-primary d-flex align-items-center text-reset p-2">
                                    <i class="las la-home mr-2 fs-16"></i><span>{{ __('dashboard.nav.dashboard') }}</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/orders') }}" class="aiz-side-nav-link d-flex align-items-center text-reset p-2 opacity-60">
                                    <i class="las la-file-invoice mr-2 fs-16"></i><span>{{ __('dashboard.nav.purchase_history') }}</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="#" class="aiz-side-nav-link d-flex align-items-center text-reset p-2 opacity-60">
                                    <i class="las la-download mr-2 fs-16"></i><span>{{ __('dashboard.nav.downloads') }}</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="#" class="aiz-side-nav-link d-flex align-items-center text-reset p-2 opacity-60">
                                    <i class="las la-reply mr-2 fs-16"></i><span>{{ __('dashboard.nav.refund_sent') }}</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/wishlist') }}" class="aiz-side-nav-link d-flex align-items-center text-reset p-2 opacity-60">
                                    <i class="las la-heart mr-2 fs-16"></i><span>{{ __('dashboard.nav.wishlist') }}</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ route('wallet.index') }}" class="aiz-side-nav-link d-flex align-items-center text-reset p-2 opacity-60">
                                    <i class="las la-wallet mr-2 fs-16"></i><span>{{ __('dashboard.nav.wallet') }}</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/profile') }}" class="aiz-side-nav-link d-flex align-items-center text-reset p-2 opacity-60">
                                    <i class="las la-user mr-2 fs-16"></i><span>{{ __('dashboard.nav.profile') }}</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- CONTENIDO --}}
            <div class="col-lg-9">
                <h3 class="h4 fw-700 mb-4">{{ __('dashboard.greeting', ['name' => Auth::user()->name]) }}</h3>

                @include('partials.shop-status-banner')

                <div class="verify-alert d-flex align-items-start mb-4">
                    <i class="las la-exclamation-triangle verify-icon mr-3 mt-1"></i>
                    <div>
                        <strong class="verify-title d-block mb-1">{{ __('dashboard.verify.title') }}</strong>
                        <span class="verify-text">{{ __('dashboard.verify.text') }}</span>
                        <div class="mt-3 d-flex flex-wrap gap-2">
                            <a href="{{ route('verification.notice') }}" class="btn btn-sm btn-warning fw-600">
                                <i class="las la-envelope mr-1"></i> {{ __('dashboard.verify.verify_btn') }}
                            </a>
                            <form method="POST" action="{{ route('verification.resend') }}" class="d-inline ml-2">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-warning fw-600">
                                    <i class="las la-redo-alt mr-1"></i> {{ __('dashboard.verify.resend_btn') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="row gutters-10 mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="wave-card" style="background: linear-gradient(135deg, #ee9ca7, #ffdde1);">
                            <i class="las la-shopping-cart stat-icon"></i>
                            <h2 class="fw-700 fs-32 mb-1 position-relative">{{ $cartCount ?? 0 }}</h2>
                            <p class="mb-0 opacity-80 fs-14 position-relative">{{ __('dashboard.stats.cart') }}</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="wave-card" style="background: linear-gradient(135deg, #4776e6, #8e54e9);">
                            <i class="las la-heart stat-icon"></i>
                            <h2 class="fw-700 fs-32 mb-1 position-relative">{{ $wishlistCount ?? 0 }}</h2>
                            <p class="mb-0 opacity-80 fs-14 position-relative">{{ __('dashboard.stats.wishlist') }}</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="wave-card" style="background: linear-gradient(135deg, #6a85b6, #bac8e0);">
                            <i class="las la-box stat-icon"></i>
                            <h2 class="fw-700 fs-32 mb-1 position-relative">{{ $orderCount ?? 0 }}</h2>
                            <p class="mb-0 opacity-80 fs-14 position-relative">{{ __('dashboard.stats.ordered') }}</p>
                        </div>
                    </div>
                </div>

                <div class="row gutters-10">
                    <div class="col-md-7 mb-3">
                        <div class="info-card p-4 h-100">
                            <h4 class="h6 fw-600 mb-3 opacity-60"><i class="las la-map-marker mr-1 text-primary"></i> {{ __('dashboard.address.title') }}</h4>
                            @if(isset($address) && $address)
                                <p class="fs-14 mb-1 fw-600">{{ $address->full_name }}</p>
                                <p class="fs-13 opacity-70 mb-1">{{ $address->address }}</p>
                                <p class="fs-13 opacity-70 mb-1">{{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}</p>
                                <p class="fs-13 opacity-70 mb-0">{{ $address->phone }}</p>
                            @else
                                <div class="opacity-50 fs-13 mt-4 text-center py-3">
                                    <i class="las la-map-marker la-2x mb-2 d-block text-primary"></i>
                                    {{ __('dashboard.address.empty') }}
                                    <div class="mt-3"><a href="{{ url('/profile') }}" class="btn btn-sm btn-outline-primary">{{ __('dashboard.address.add') }}</a></div>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-5 mb-3">
                        <div class="info-card p-4 h-100 text-center d-flex flex-column justify-content-center align-items-center">
                            <i class="las la-box-open la-3x text-primary mb-3 opacity-50"></i>
                            <h4 class="h6 fw-600 mb-2 opacity-60">{{ __('dashboard.package.title') }}</h4>
                            <h5 class="fw-600 text-primary mb-4">{{ $package->name ?? __('dashboard.package.not_found') }}</h5>
                            <a href="{{ url('/packages') }}" class="btn btn-primary fw-600 px-4">{{ __('dashboard.package.upgrade') }}</a>
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
        <h5 class="fw-700 mb-1">{{ __('dashboard.logout.title') }}</h5>
        <p class="opacity-60 fs-14 mb-4">{{ __('dashboard.logout.text') }}</p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout-confirm"><i class="las la-sign-out-alt mr-1"></i> {{ __('dashboard.logout.confirm') }}</button>
        </form>
        <button class="btn-logout-cancel" onclick="document.getElementById('logoutModal').classList.remove('show')">{{ __('dashboard.logout.cancel') }}</button>
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